<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author sunjing@shopex.cn
 * @describe 一键代发发货单
 */
class erpapi_wms_matrix_yjdf_request_delivery extends erpapi_wms_request_delivery
{
    //配送方式(1:京东配送,2:京配转三方配送,3:第三方配送,4:普通快递配送)
    private $_shipping_type = 0;
    
    //配送费用
    private $_shipping_cost = 0;
    
    //京东云交易渠道ID
    private $_channel_id = null;
    
    private $_shop_type_mapping = array(
        'taobao'   => 'TB',
        'paipai'   => 'PP',
        '360buy'   => 'JD',
        'qq_buy'   => 'QQ',
        'dangdang' => 'DD',
        'alibaba'  => '1688',
        'suning'   => 'SN',
        'gome'     => 'GM',
        'vop'      => 'WPH',
        'kuaishou' => 'KS',
        'luban' => 'DY',
    );
    
    /**
     * 发货单创建
     * 
     * @return void
     * @author
     * */

    public function delivery_create($sdf)
    {
        $deliveryObj = app::get('ome')->model('delivery');
        $operLogObj = app::get('ome')->model('operation_log');
        
        $delivery_bn = $sdf['outer_delivery_bn'];
        $delivery_id = $sdf['delivery_id'];
        $gateway = '';
        
        $iscancel = kernel::single('ome_interface_delivery')->iscancel($delivery_bn);
        if ($iscancel) {
            return $this->succ('发货单已取消,终止同步');
        }
        
        //检查是否存在京东订单号
        $sql = "SELECT package_id FROM sdb_ome_delivery_package WHERE delivery_id=". $delivery_id ." AND status IN('accept', 'delivery')";
        $isCheck = $deliveryObj->db->selectrow($sql);
        if($isCheck){
            return $this->succ('发货单已经有京东订单号,直接返回成功');
        }
        
        //获取发货单上的渠道ID
        $this->getProductChannelId($sdf);
        
        //免邮配送(第三方仓储管理栏目中设置)
        if($this->__channelObj->wms['crop_config']['shipping_cost'] == 'free_postage'){
            //[免邮]配送方式
            $this->_shipping_type = '4';
            
            //[免邮]配送费用
            $this->_shipping_cost = 0;
        }else{
            //通过APi接口获取京东配送方式
            if(empty($sdf['shipping_type'])){
                $result = $this->delivery_shipping($sdf);
                if($result['rsp'] != 'succ'){
                    return $result;
                }
            }else{
                $this->_shipping_type = $sdf['shipping_type'];
            }
            
            //通过APi接口获取京东配送费用
            if(empty($sdf['delivery_cost_actual']) || bccomp('0.000', $sdf['delivery_cost_actual'], 3)==0){
                $result = $this->delivery_postage($sdf);
                if($result['rsp'] != 'succ'){
                    return $result;
                }
            }else{
                $this->_shipping_cost = number_format($sdf['delivery_cost_actual'], 0, ".", "");
            }
        }
        
        //推送发货单
        $title = $this->__channelObj->wms['channel_name'] . '发货单添加';
        
        //加密推送标识(0为未加密,1为加密)
        $is_encryptedOrder = 0;
        
        /***
        //抖音平台订单强制密文推送
        if ($sdf['shop_type'] == 'luban' || $this->_needEncryptOriginData($sdf)) {
            $this->_getEncryptOriginData($sdf);
            
            //订单密文推送
            $is_encryptedOrder = 1;
        }
     * **/
        
        //params
        $params = $this->_format_delivery_create_params($sdf);
        if (!$params) {
            return $this->error('参数为空,终止同步');
        }
        
        //判断是否加密
        $gateway = '';
        if (kernel::single('ome_security_router', $sdf['shop_type'])->is_encrypt($sdf, 'delivery')) {
            $params['s_node_type'] = $sdf['shop_type'];
            $params['s_node_id']   = $sdf['node_id'];
            
            //[兼容]config是序列化字符
            if(is_string($this->__channelObj->wms['config'])){
                $this->__channelObj->wms['config'] = unserialize($this->__channelObj->wms['config']);
            }
            
            // 加密推送
            $need_encrypt_list = $this->__channelObj->wms['config']['need_encrypt'];
            if ($need_encrypt_list && $need_encrypt_list[$sdf['shop_type']]) {
                $params['need_encrypt'] = 'true';
                
                //订单密文推送
                $is_encryptedOrder = 1;
            }
            
            $params['order_bns'] = implode(',', explode('|', $sdf['order_bn']));
            
            $gateway = $sdf['shop_type'];
        }else{
            //加密推送
            $need_encrypt_list = $this->__channelObj->wms['config']['need_encrypt'];
            if ($need_encrypt_list && $need_encrypt_list[$sdf['shop_type']]) {
                //订单密文推送
                $is_encryptedOrder = 1;
            }
        }
        
        //订单是否加密
        $params['encryptedOrder'] = $is_encryptedOrder;
        
        //更新京东云交易采购价格
        $deliveryLib = kernel::single('console_delivery');
        $error_msg = '';
        $result = $deliveryLib->updatePurchasePrice($params, $error_msg);
        
        //callback
        $callback = array(
                'class'  => get_class($this),
                'method' => 'delivery_create_callback',
                'params' => array(
                    'delivery_bn' => $delivery_bn, 
                    'obj_bn'      => $delivery_bn, 
                    'obj_type' => 'delivery', //推送失败类型(erpapi_api_fail重试记录)
                    'delivery_send_second_after_first'=>$this->__channelObj->wms['crop_config']['delivery_send_second_after_first']
                ),
        );
        
        
        //提前保存请求失败日志(定时任务一个月后会自动删除)
        $retry = array(
                'obj_bn'        => $delivery_bn,
                'obj_type'      => 'delivery',
                'channel'       => 'wms',
                'channel_id'    => $this->__channelObj->wms['channel_id'],
                'method'        => 'delivery_create',
                'args'          => func_get_args(),
                'next_obj_type' => $this->_getNextObjType()
        );
        $apiFailId = app::get('erpapi')->model('api_fail')->saveRunning($retry);
        if($apiFailId) {
            $callback['params']['api_fail_id'] = $apiFailId;
        }
        
        
        //request
        return $this->__caller->call($this->_get_create_api_name(), $params, $callback, $title, 10, $delivery_bn, true, $gateway);
    }
    
        /**
     * delivery_create_callback
     * @param mixed $response response
     * @param mixed $callback_params 参数
     * @return mixed 返回值
     */
    public function delivery_create_callback($response, $callback_params)
    {
        $res = $response['res']; //error_code错误编码
        $rsp = $response['rsp'];

        $rs = parent::delivery_create_callback($response, $callback_params);


        $dly = app::get('ome')->model('delivery')->db_dump(array('delivery_bn' => $callback_params['delivery_bn']), 'delivery_id,sync_send_succ_times,ship_province,ship_city,ship_district,ship_town,ship_village,ship_addr');
        if($response['rsp'] == 'succ' && $callback_params['delivery_send_second_after_first']) {
            if($dly['sync_send_succ_times'] == 1) {
                $time = time() + $callback_params['delivery_send_second_after_first'] * 60;
                $task = array(
                    'obj_id'    => $dly['delivery_id'],
                    'obj_type'  => 'delivery_send_again',
                    'exec_time' => $time,
                );
                app::get('ome')->model('misc_task')->saveMiscTask($task);
                app::get('ome')->model('operation_log')->write_log('delivery_modify@ome',$dly['delivery_id'],"二次推送时间：".date('Y-m-d H:i', $time));
            }
        }

        // 缺货发货单，检查库存
        // if ($rsp == 'fail' && in_array($res, ['6000','4900_100'])) {
        //     $deliveryItemMdl = app::get('ome')->model('delivery_items');
        //     $items = $deliveryItemMdl->getList('product_id,number',array ('delivery_id' => $dly['delivery_id']));

        //     $data = $dly;
        //     $data['log_id']     = 'stocksync-' . $dly['delivery_bn'];
        //     $data['task_type']  = 'stocksync';
        //     $data['items']      = json_encode($items);

        //     $push_params = array(
        //         'data' => $data,
        //         'url' => kernel::openapi_url('openapi.autotask','service')
        //     );

        //     kernel::single('taskmgr_interface_connecter')->push($push_params);
        // }
        
        return $rs;
    }
    
    /**
     * 格式化参数
     * 
     * @param array $sdf
     * @return array
     */
    protected function _format_delivery_create_params($sdf)
    {
        //订单号(有多个发货单合并订单的情况)
        //$order_bns = explode(',', $sdf['order_bn']);
        
        $price_model = $this->__channelObj->wms['crop_config']['price_model'];
        
        //格式化价格
        if($price_model == 'shop_price'){
            //读取平台店铺实付价格
            $sdf = $this->_formatDeliveryShopPrice($sdf);
        }elseif($price_model == 'shop_original_price'){
            //平台商品原价
            $sdf = $this->_formatDeliveryShopOriginalPrice($sdf);
        }else{
            //读取京东云交易价格(默认)
            $sdf = $this->_formatDeliveryWmsPrice($sdf);
        }
        
        //京东一件代发标识
        $sdf['wms_node_type'] = $this->__channelObj->wms['node_type'];
        
        //商品总金额
        $totalSalePrice = $sdf['totalSalePrice'];
        if (isset($sdf['subsidyFee'])) {
            $subsidyFee = round(array_sum(array_column($sdf['subsidyFee'],'subsidyFee')),3);
        }
        
        //params
        $params = parent::_format_delivery_create_params($sdf);
        
        //使用发货单商品上的渠道ID
        if($this->_channel_id){
            $params['warehouse_code'] = $this->_channel_id;
        }
        
        //获取京标地址(需要配置京标模式)
        $params['jd_state_id'] = 0;
        $params['jd_city_id'] = 0;
        $params['jd_county_id'] = 0;
        $params['jd_town_id'] = 0;
        if ($this->__channelObj->wms['crop_config']['address_type'] == 'j') {
            $wms_id = $this->__channelObj->wms['channel_id'];
            
            //[乡镇]四级地区
            $receiver_town = $params['receiver_town'];
            
            //[兼容]四级地区-乡镇,从详细地址上取
            //@todo：后面四级地址稳定后,需要删除这段兼容代码
            $address = $params['receiver_address'];
            if(empty($receiver_town) && $address){
                if (strpos($address,',')) {
                    $receiver_town = substr($address,0,strpos($address,','));
                }else{
                    preg_match('/(.*?(镇|乡|街道))/', $address,$matches);
                    if ($matches) {
                        $receiver_town = current($matches);
                    }
                }
            }
            
            if ($wms_id) {
                $object = kernel::single('erpapi_router_request')->set('wms', $wms_id);
                $platform_area = $object->branch_getAreaId([
                    'ship_province' => $params['receiver_state'],
                    'ship_city'     => $params['receiver_city'],
                    'ship_district' => $params['receiver_district'],
                    'ship_town'     => $receiver_town,
                    'ship_addr'     => $params['receiver_address'],
                    'original_bn'   => $sdf['outer_delivery_bn'],
                ]);
                
                if ($platform_area['rsp'] == 'succ') {
                    $params['jd_state_id']  = (int)$platform_area['data']['provinceid'];
                    $params['jd_city_id']   = (int)$platform_area['data']['cityid'];
                    $params['jd_county_id'] = (int)$platform_area['data']['streetid'];
                    $params['jd_town_id']   = (int)$platform_area['data']['townid'];
                }
            }
        }else{
            //[国标]地址格式:直接把四级地区加到详细地址里
            if(strpos($params['receiver_address'], $params['receiver_town']) === false){
                $params['receiver_address'] = $params['receiver_town'] . $params['receiver_address'];
            }
        }
        
        if ($sdf['source'] == 'matrix') {
            $params['order_source'] = $this->_shop_type_mapping[$sdf['shop_type']] ? $this->_shop_type_mapping[$sdf['shop_type']] : 'OTHER';
        } else {
            $params['order_source'] = 'OTHER';
        }
        
        if($rdIndex = strpos($params['receiver_address'], $params['receiver_district'])) {
            $rdIndex += strlen($params['receiver_district']);
            $params['receiver_address'] = substr($params['receiver_address'], $rdIndex);
        }
        
        $params['receiver_zip']    = $params['receiver_zip'] ?: '000000';
        $params['receiver_email']  = $params['receiver_email'] ?: 'testyf@jd.com';
        $params['shipping_type']   = $this->_shipping_type; //配送方式
        $params['shipping_fee']    = $this->_shipping_cost; //配送费用(京东字段名：freightFee)
        $params['total_goods_fee'] = $totalSalePrice; //商品总金额(京东字段名：orderFee)
        
        if(isset($sdf['discount_fee']) && $price_model == 'shop_original_price'){
            $params['discount_fee'] = $sdf['discount_fee']; //商品总优惠金额(京东字段名：discountFee)
        }else{
            //删除掉discount_fee字段,否则京东报错
            unset($params['discount_fee']);
        }
        
        //加上配送费用
        $total_trade_fee = empty($this->_shipping_cost) ? $totalSalePrice : ($totalSalePrice + $this->_shipping_cost);
        
        $params['total_trade_fee'] = $total_trade_fee; //商品总金额 + 配送费用
        //$params['member_uname']    = $sdf['member_name']; //会员名暂时用不上,而且是加密的密文
        $params['user_ip']         = kernel::single('base_request')->get_remote_addr() ?: '127.0.0.1';
        $params['pin'] = $this->__channelObj->wms['crop_config']['pin'];
        
        //默认模式为3：佣金订单+补贴订单
        $settle_model = $this->__channelObj->wms['crop_config']['settle_model'];
        
        //订单优惠(补贴信息列表)
        if(in_array($settle_model, array('2', '3'))) {
            $subsidyList = $this->_getSubsidyList($sdf);
            
            $params['settle_model'] = 2;
            $params['subsidy_list'] = json_encode($subsidyList);
        }
        
        //订单佣金(佣金信息列表)
        if($settle_model == '3'){
            $commissionList = array();
            $brokedata = $this->_getCommission($sdf);
            if($brokedata){
                foreach ($brokedata as $key => $val)
                {
                    $commissionList[] = array(
                            'subCommissionId' => (string)$val['short_id'], //分佣id(达人short_id)
                            'subCommissionName' => (string)$val['author_account'], //分佣名称(达人账户)
                            'subCommissionFee' => $val['real_comission'], //分佣金额
                            'subCommissionRatio' => $val['commission_rate'], //分佣比例
                    );
                }
            }
            
            //[兼容]没有主播佣金默认传空
            if(empty($commissionList)){
                $commissionList[] = array(
                        'subCommissionId' => '0', //分佣id
                        'subCommissionName' => '0', //分佣名称
                        'subCommissionFee' => 0, //分佣金额
                        'subCommissionRatio' => 0, //分佣比例
                );
            }
            
            $params['settle_model'] = 3;
            $params['commission_list'] = json_encode($commissionList);
        }
        
        //开发票信息
        $invoice = (array) $this->__channelObj->wms['crop_config']['invoice'];
        $params['has_invoice'] = $invoice['type'] ? 'true' : 'false';
        if ($params['has_invoice'] == 'true') {
            $params['invoice_type'] = $invoice['type'];
            switch ($invoice['type']) {
                case '2': // 增票
                    $params['invoice_title']         = $invoice['vat_companyName'];
                    $params['payer_register_no']     = $invoice['vat_code'];
                    $params['invoice_reg_address']   = $invoice['vat_regAddr'];
                    $params['invoice_reg_phone']     = $invoice['vat_regPhone'];
                    $params['invoice_bank_name']     = $invoice['vat_regBank'];
                    $params['invoice_bank_account']  = $invoice['vat_regBankAccount'];
                    $params['invoice_receiver_name'] = $invoice['vat_consigneeName'];
                    $params['invoice_buyer_phone']   = $invoice['vat_consigneeMobile'];
                    $params['invoice_address']       = $invoice['vat_consigneeAddr'];
                    break;
                case '3': // 电子票
                    //开票类型：对应京东开普勒selectedInvoiceTitle字段名,必须是数字类型;
                    $params['invoice_kind'] = $invoice['elect_mode'];
                    
                    $params['invoice_title']       = ($invoice['elect_title'] ? $invoice['elect_title'] : $invoice['elect_companyName']);
                    $params['payer_register_no']   = $invoice['elect_code'];
                    $params['invoice_buyer_email'] = $invoice['elect_consigneeEmail'];
                    $params['invoice_buyer_phone'] = $invoice['elect_consigneePhone'];

                    // 4：个人 5：公司
                    if ($params['elect_title'] == '个人' || $params['invoice_kind'] == '4') {
                        $params['invoice_kind'] = '4';
                        $params['invoice_title']        = '';
                        $params['payer_register_no']    = '';
                        $params['invoice_buyer_email']  = '';
                        $params['invoice_buyer_phone']  = $params['receiver_mobile'];
                    }
                    
                    break;
            }
        }
        
        //平台订单信息
        $platformOrderInfo = $this->_getPlatformOrderInfo($sdf);
        $params['outPlatformOrderInfo'] = json_encode($platformOrderInfo);
        
        return $params;
    }

    /**
     * 发货单取消
     * 
     * @return void
     * @author
     * */
    public function delivery_cancel($sdf)
    {
        $packageObj = app::get('ome')->model('delivery_package');
        
        $title = $this->__channelObj->wms['channel_name'] . '发货单取消';
        $delivery_bn = $sdf['outer_delivery_bn'];
        $deliveryId = $sdf['outer_delivery_id'];
        
        //获取发货单上的渠道ID
        $this->getProductChannelId($sdf);
        
        //没有获取到京东包裹
        $packageRows = $packageObj->getList('package_id,package_bn,status', array('delivery_id'=>$deliveryId,'status|noequal'=>'cancel'));
        if(empty($packageRows)){
            //推送京东云交易失败,直接取消发货单
            if($sdf['sync_status'] == '2'){
                return $this->succ('发货单推送给仓库失败,允许直接取消发货单。');
            }
            
            //没有京东订单号
            $sdf['original_delivery_bn'] = ($sdf['original_delivery_bn'] ? $sdf['original_delivery_bn'] : $delivery_bn);
            $sdf['cancel_type'] = 1;
            
            //request
            $params = $this->_format_delivery_cancel_params($sdf);
            
            $result = $this->__caller->call(WMS_SALEORDER_CANCEL, $params, null, $title, 10, $delivery_bn);

            $data = @json_decode($result['data'], true);

            $result['rsp'] = $data['cancelStatus'] == '3' ? 'succ' : 'fail';
            
            //[兼容场景]手工补单时,退款单和自动审单同时进行,导致没有获取到京东订单号
            //todo：自动审单生成发货单,京东云交易MQ隔2秒后才返回京东订单号
            if($result['rsp'] != 'succ'){
                
                //隔3秒钟,防止退款单和京东订单号同时创建
                sleep(3);
                
                $queueObj = app::get('base')->model('queue');
                $queueData = array(
                        'queue_title' => '发货单号：'. $delivery_bn .'自动取消京东订单号',
                        'start_time' => time(),
                        'params' => array(
                                'sdfdata' => array('delivery_id'=>$deliveryId, 'delivery_bn'=>$delivery_bn),
                                'app' => 'console',
                                'mdl' => 'delivery',
                        ),
                        'worker' => 'console_delivery.autoCancelPackage',
                );
                $queueObj->save($queueData);
            }
            
            return $result;
        }
        
        $result = array('rsp'=>'succ');

        // 按子包裹纬度进行取消
        foreach ((array) array_column($packageRows, null, 'package_id') as $v) {
            if ($v['status'] == 'delivery') {
                $result['rsp'] = 'fail';
                $result['msg'] = sprintf('包裹[%s]已发货，等待拦截', $v['package_bn']);
            }

            $sdf['original_delivery_bn'] = $v['package_bn'];
            $sdf['cancel_type'] = 1;


            $params = $this->_format_delivery_cancel_params($sdf);

            $rsp = $this->__caller->call(WMS_SALEORDER_CANCEL, $params, null, $title, 10, $delivery_bn);
            $rsp['data'] = @json_decode($rsp['data'], true);

            if($rsp['rsp'] != 'succ' || $rsp['data']['cancelStatus'] == '1') {
                $result['rsp'] = 'fail';
                $result['msg'] = sprintf('包裹[%s]%s', $v['package_bn'],$rsp['err_msg']);
            }
        }

        return $this->error('包裹拦截中', '');
    }

    protected function _format_delivery_cancel_params($sdf)
    {
        //使用发货单商品上的渠道ID
        if(empty($this->_channel_id)){
            $this->_channel_id = $this->get_warehouse_code($this->__channelObj->wms['channel_id'], $sdf['branch_bn']);
        }
        
        //params
        $params = array(
            'warehouse_code' => $this->_channel_id, //渠道ID
            'trade_code'     => $sdf['original_delivery_bn'],
            'pin'            =>  $this->__channelObj->wms['crop_config']['pin'],
            'reason'         => '100',
            'reason_type'    => $sdf['reason_type'], # 取消类型。1：未支付取消 2：用户取消 3：风控取消
            'cancel_type'    => $sdf['cancel_type'], # 取消类型。1：订单取消；2：订单拦截
        );
        
        return $params;
    }
    
    /**
     * 获取配送方式
     * 
     * @param array $sdf
     * @return array
     */
    protected function _format_shipping_type_params($sdf)
    {
        $foreignObj = app::get('console')->model('foreign_sku');
        
        $create_time = preg_match('/-|\//', $sdf['create_time']) ? $sdf['create_time'] : date("Y-m-d H:i:s", $sdf['create_time']);
        
        //京东云交易渠道ID
        if(empty($this->_channel_id)){
            $this->_channel_id = $this->get_warehouse_code($this->__channelObj->wms['channel_id'], $sdf['branch_bn']);
        }
        
        //params
        $params = array(
                'cod_fee' => $sdf['cod_fee'], //应收货款（用于货到付款）
                'cod_service_fee' => '0', //cod服务费（货到付款 必填）
                'warehouse_code' => $this->_channel_id, //仓库编码
                'receiver_state' => $sdf['consignee']['province'], //省份
                'receiver_city' => $sdf['consignee']['city'], //北京市
                'receiver_district' => $sdf['consignee']['district'], //大兴区
                'receiver_address' => $sdf['consignee']['addr'], //详细地址
                'pin' => $this->__channelObj->wms['crop_config']['pin'], //pin
                'current_page' => 1, //当前批次,用于分批同步
                'created' => $create_time,
        );
        
        //items
        $items = array();
        $delivery_items = $sdf['delivery_items'];
        if($delivery_items){
            sort($delivery_items);
            
            //item
            foreach ($delivery_items as $k => $v)
            {
                $is_gift = ($v['is_gift'] == 'ture' ? '1' : '0');
                
                //获取京东商品信息
                $oldRow = $foreignObj->db_dump(array('inner_sku'=>$v['bn'], 'wms_id'=>$this->__channelObj->wms['channel_id']), 'outer_sku,price');
                if($oldRow){
                    $v['bn'] = $oldRow['outer_sku'];
                    $v['price'] = $oldRow['price'];
                    
                    $v['sale_price'] = bcmul($v['price'], $v['number'], 3);
                }
                
                //item
                $items['item'][] = array(
                        'item_code' => $v['bn'],
                        'item_name' => $v['product_name'],
                        'item_quantity' => (int) $v['number'],
                        'item_price' => (float) $v['price'],
                        'item_line_num' => ($k + 1), // 订单商品列表中商品的行项目编号，即第n行或第n个商品
                        'trade_code' => $sdf['order_bn'], //可选(若是淘宝交易订单，并且不是赠品，必须要传订单来源编号)
                        'item_id' => $v['bn'], // 外部系统商品sku
                        'is_gift' => $is_gift, // 是否赠品
                        'item_remark' => $v['memo'], // TODO: 商品备注
                        'inventory_type' => '1', // TODO: 库存类型1可销售库存101类型用来定义残次品201冻结类型库存301在途库存
                        'item_sale_price' => (float) $v['sale_price'], //成交额
                );
            }
        }
        $params['items'] = json_encode($items); //json
        
        //[京标]需要转换京东配送地址ID
        $params['jd_state_id'] = 0;
        $params['jd_city_id'] = 0;
        $params['jd_county_id'] = 0;
        $params['jd_town_id'] = 0;
        if($this->__channelObj->wms['crop_config']['address_type'] == 'j'){
            $wms_id = $this->__channelObj->wms['channel_id'];
            if($wms_id){
                $object = kernel::single('erpapi_router_request')->set('wms', $wms_id);
                $filter = array(
                        'ship_province' => $params['receiver_state'],
                        'ship_city'     => $params['receiver_city'],
                        'ship_district' => $params['receiver_district'],
                        'ship_town'     => $sdf['consignee']['town'],
                        'ship_addr'     => $params['receiver_address'],
                        'original_bn'   => $sdf['outer_delivery_bn'],
                );
                $platform_area = $object->branch_getAreaId($filter);
                if($platform_area['rsp'] == 'succ'){
                    $params['jd_state_id'] = (int)$platform_area['data']['provinceid'];
                    $params['jd_city_id'] = (int)$platform_area['data']['cityid'];
                    $params['jd_county_id'] = (int)$platform_area['data']['streetid'];
                    $params['jd_town_id'] = (int)$platform_area['data']['townid'];
                }
            }
        }
        
        return $params;
    }
    
    /**
     * 获取配送费用
     * 
     * @param array $sdf
     * @return array
     */
    protected function _format_cost_actual_params($sdf)
    {
        $foreignObj = app::get('console')->model('foreign_sku');
        
        $create_time = preg_match('/-|\//', $sdf['create_time']) ? $sdf['create_time'] : date("Y-m-d H:i:s", $sdf['create_time']);
        
        //京东云交易渠道ID
        if(empty($this->_channel_id)){
            $this->_channel_id = $this->get_warehouse_code($this->__channelObj->wms['channel_id'], $sdf['branch_bn']);
        }
        
        //params
        $params = array(
                'cod_fee' => $sdf['cod_fee'], //应收货款（用于货到付款）
                'cod_service_fee' => '0', //cod服务费（货到付款 必填）
                'warehouse_code' => $this->_channel_id, //渠道ID
                'receiver_state' => $sdf['consignee']['province'], //省份
                'receiver_city' => $sdf['consignee']['city'], //北京市
                'receiver_district' => $sdf['consignee']['district'], //大兴区
                'receiver_address' => $sdf['consignee']['addr'], //详细地址
                'pin' => $this->__channelObj->wms['crop_config']['pin'], //pin
                'current_page' => 1, //当前批次,用于分批同步
                'created' => $create_time,
        );
        
        //items
        $items = array();
        $delivery_items = $sdf['delivery_items'];
        $totalSalePrice = 0;
        if($delivery_items){
            sort($delivery_items);
            
            //item
            foreach ($delivery_items as $k => $v)
            {
                $is_gift = ($v['is_gift'] == 'ture' ? '1' : '0');
                
                //获取京东商品信息
                $oldRow = $foreignObj->db_dump(array('inner_sku'=>$v['bn'], 'wms_id'=>$this->__channelObj->wms['channel_id']), 'outer_sku,price');
                if($oldRow){
                    $v['bn'] = $oldRow['outer_sku'];
                    $v['price'] = $oldRow['price'];
                    
                    $v['sale_price'] = bcmul($v['price'], $v['number'], 3);
                }
                
                //item
                $items['item'][] = array(
                        'item_code' => $itemCode[$v['product_id']] ? $itemCode[$v['product_id']] : $v['bn'],
                        'item_name' => $v['product_name'],
                        'item_quantity' => (int) $v['number'],
                        'item_price' => (float) $v['price'],
                        'item_line_num' => ($k + 1), // 订单商品列表中商品的行项目编号，即第n行或第n个商品
                        'trade_code' => $sdf['order_bn'], //可选(若是淘宝交易订单，并且不是赠品，必须要传订单来源编号)
                        'item_id' => $v['bn'], // 外部系统商品sku
                        'is_gift' => $is_gift, // 是否赠品
                        'item_remark' => $v['memo'], // TODO: 商品备注
                        'inventory_type' => '1', // TODO: 库存类型1可销售库存101类型用来定义残次品201冻结类型库存301在途库存
                        'item_sale_price' => (float) $v['sale_price'], //成交额
                );
                
                //发货单总金额
                $totalSalePrice = bcadd($v['sale_price'], $totalSalePrice, 3);
            }
        }
        $params['items'] = json_encode($items); //json
        
        //发货单总额
        $params['total_goods_fee'] = $totalSalePrice;
        
        //[京标]需要转换京东配送地址ID
        $params['jd_state_id'] = 0;
        $params['jd_city_id'] = 0;
        $params['jd_county_id'] = 0;
        $params['jd_town_id'] = 0;
        if($this->__channelObj->wms['crop_config']['address_type'] == 'j'){
            $wms_id = $this->__channelObj->wms['channel_id'];
            if($wms_id){
                $object = kernel::single('erpapi_router_request')->set('wms', $wms_id);
                $filter = array(
                        'ship_province' => $params['receiver_state'],
                        'ship_city'     => $params['receiver_city'],
                        'ship_district' => $params['receiver_district'],
                        'ship_town'     => $sdf['consignee']['town'],
                        'ship_addr'     => $params['receiver_address'],
                        'original_bn'   => $sdf['outer_delivery_bn'],
                );
                $platform_area = $object->branch_getAreaId($filter);
                if($platform_area['rsp'] == 'succ'){
                    $params['jd_state_id'] = (int)$platform_area['data']['provinceid'];
                    $params['jd_city_id'] = (int)$platform_area['data']['cityid'];
                    $params['jd_county_id'] = (int)$platform_area['data']['streetid'];
                    $params['jd_town_id'] = (int)$platform_area['data']['townid'];
                }
            }
        }
        
        return $params;
    }
    
    /**
     * 查询京东包裹发货状态
     * 
     * @param unknown $sdf
     */
    public function delivery_package_status($sdf)
    {
        $deliveryObj = app::get('ome')->model('delivery');
        $packageObj = app::get('ome')->model('delivery_package');
        $operLogObj = app::get('ome')->model('operation_log');
        
        $delivery_id = $sdf['delivery_id'];
        $package_bn = $sdf['package_bn'];
        $package_id = $sdf['package_id'];
        
        //发货单信息
        $deliveryInfo = $deliveryObj->dump(array('delivery_id'=>$delivery_id), 'delivery_id,delivery_bn,wms_channel_id');
        $delivery_bn = $deliveryInfo['delivery_bn'];
        
        //使用发货单上的渠道ID
        $this->_channel_id = $deliveryInfo['wms_channel_id'];
        if(empty($this->_channel_id)){
            $this->_channel_id = $this->get_warehouse_code($this->__channelObj->wms['channel_id'], $sdf['branch_bn']);
        }
        
        //title
        $title = $this->__channelObj->wms['channel_name'] . '查询包裹发货状态(京东包裹号：'. $package_bn .')';
        
        //请求IP地址
        $sdf['remote_addr'] = base_request::get_remote_addr();
        if(empty($sdf['remote_addr'])){
            $sdf['remote_addr'] = kernel::single('base_component_request')->get_server('SERVER_ADDR');
        }
        
        if(empty($sdf['remote_addr'])){
            $sdf['remote_addr'] = '127.0.0.1';
        }
        
        //params
        $params = array(
                'warehouse_code' => $this->_channel_id, //渠道ID
                'trade_code' => $package_bn, //京东订单号(包裹单号)
                'pin' => $this->__channelObj->wms['crop_config']['pin'], //pin
                'client_ip' => $sdf['remote_addr'], //商家操作的客户端IP
        );
        
        //判断是否加密
        $gateway = '';
        
        //request
        $callback = array();
        $packageDly = array();
        $result = $this->__caller->call(WMS_SALEORDER_DELIVERY_STATUS, $params, $callback, $title, 10, $delivery_bn, true, $gateway);
        if($result['rsp'] == 'succ')
        {
            //[兼容]格式化数据
            if(is_string($result['data'])){
                $result['data'] = json_decode($result['data'], true);
            }
            
            //包裹发货状态
            $packageDly = array('orderStatus'=>trim($result['data']['baseOrderInfo']['orderStatus']));
            
            //更新包裹的配送状态
            $packageObj->update(array('shipping_status'=>$packageDly['orderStatus']), array('package_id'=>$package_id));
            
            //log
            $operLogObj->write_log('delivery_modify@ome', $delivery_id, '京东包裹号：'. $package_bn .',查询包裹发货状态为:'. $packageDly['orderStatus']);
            
            return $this->succ('查询包裹发货状态成功', '200', $packageDly);
        }
        else
        {
            $error_msg = '京东包裹号：'. $package_bn .',查询包裹发货状态失败：';
            $error_msg .= ($result['err_msg'] ? $result['err_msg'] : $result['msg']);
            $error_msg = substr($error_msg, 0, 150); //截取150个字符
            
            $msgcode = '';
            $operLogObj->write_log('delivery_modify@ome', $delivery_id, $error_msg);
            
            return $this->error($error_msg, $msgcode);
        }
    }
    
    /**
     * 获取配送方式
     * 
     * @param array $sdf
     * @return array
     */
    public function delivery_shipping($sdf)
    {
        $deliveryObj = app::get('ome')->model('delivery');
        $operLogObj = app::get('ome')->model('operation_log');
        
        $title = $this->__channelObj->wms['channel_name'] . '发货单获取配送方式';
        $delivery_bn = $sdf['outer_delivery_bn'];
        $delivery_id = $sdf['delivery_id'];
        $gateway = '';
        
        //params
        $params = $this->_format_shipping_type_params($sdf);
        if (!$params) {
            return $this->error('参数为空,终止同步');
        }
        
        //request
        $callback = array();
        $result = $this->__caller->call(WMS_SALEORDER_SHIPMENT, $params, $callback, $title, 10, $delivery_bn, true, $gateway);
        if($result['rsp'] != 'succ'){
            $error_msg = '获取配送方式失败：';
            $error_msg .= $result['err_msg'] ? $result['err_msg'] : $result['msg'];
            $msgcode = '';
            
            $operLogObj->write_log('delivery_modify@ome', $delivery_id, $error_msg);
            
            return $this->error($error_msg, $msgcode);
        }
        
        //[兼容]格式化数据
        if(is_string($result['data'])){
            $result['data'] = json_decode($result['data'], true);
        }
        
        //更新配送方式
        $shipmentType = $result['data']['shipmentType'];
        $this->_shipping_type = $shipmentType;
        
        $deliveryObj->update(array('shipping_type'=>$shipmentType), array('delivery_bn'=>$delivery_bn));
        
        $operLogObj->write_log('delivery_modify@ome', $delivery_id, '获取配送方式成功,配送方式：'. $shipmentType);
        
        return $this->succ('获取配送方式成功');
    }
    
    /**
     * 获取配送费用
     * 
     * @param array $sdf
     * @return array
     */
    public function delivery_postage($sdf)
    {
        $deliveryObj = app::get('ome')->model('delivery');
        $operLogObj = app::get('ome')->model('operation_log');
        
        $title = $this->__channelObj->wms['channel_name'] . '发货单获取配送费用';
        $delivery_bn = $sdf['outer_delivery_bn'];
        $delivery_id = $sdf['delivery_id'];
        $gateway = '';
        
        //params
        $params = $this->_format_cost_actual_params($sdf);
        if (!$params) {
            return $this->error('参数为空,终止同步');
        }
        
        //request
        $callback = array();
        $result = $this->__caller->call(WMS_SALEORDER_SHIPPING, $params, $callback, $title, 10, $delivery_bn, true, $gateway);
        if($result['rsp'] != 'succ'){
            $error_msg = '获取配送费用失败：';
            $error_msg .= $result['err_msg'] ? $result['err_msg'] : $result['msg'];
            $msgcode = '';
            
            $operLogObj->write_log('delivery_modify@ome', $delivery_id, $error_msg);
            
            return $this->error($error_msg, $msgcode);
        }
        
        //[兼容]格式化数据
        if(is_string($result['data'])){
            $result['data'] = json_decode($result['data'], true);
        }
        
        //更新配送方式
        $delivery_cost_actual = (float)$result['data']['freightFee'];
        $this->_shipping_cost = $delivery_cost_actual;
        
        $deliveryObj->update(array('delivery_cost_actual'=>$delivery_cost_actual, 'delivery_cost_expect'=>$delivery_cost_actual), array('delivery_bn'=>$delivery_bn));
        
        $operLogObj->write_log('delivery_modify@ome', $delivery_id, '获取配送费用成功,配送费用：'. $delivery_cost_actual);
        
        return $this->succ('获取配送方式成功');
    }
    
    /**
     * 通知提前发货
     * 
     * @param array $sdf
     * @return array
     */
    public function delivery_makedly($sdf)
    {
        $deliveryObj = app::get('ome')->model('delivery');
        $operLogObj = app::get('ome')->model('operation_log');
        
        $deliveryLib = kernel::single('console_delivery');
        
        $title = $this->__channelObj->wms['channel_name'] . '通知提前发货';
        $delivery_id = $sdf['delivery_id'];
        $delivery_bn = $sdf['delivery_bn'];
        $msgcode = '';
        $gateway = '';
        
        //获取发货单上的渠道ID
        $this->getProductChannelId($sdf);
        
        //获取发货仓库对应的渠道ID
        if(empty($this->_channel_id)){
            $this->_channel_id = $this->get_warehouse_code($this->__channelObj->wms['channel_id'], $sdf['branch_bn']);
        }
        
        //获取发货单对应所有包裹
        $error_msg = '';
        $status = '';
        $packageList = $deliveryLib->getDeliveryPackage($delivery_id, $error_msg, $status);
        if(empty($packageList)){
            $error_msg = '没有找到包裹';
            return $this->error($error_msg, $msgcode);
        }
        
        //request
        foreach ($packageList as $key => $val)
        {
            $package_bn = $val['package_bn'];
            
            //check
            if(in_array($val['status'], array('return_back','cancel','delivery'))){
                continue;
            }
            
            //params
            $params = array(
                    'warehouse_code' => $this->_channel_id,
                    'trade_code' => $package_bn, //京东服务单号
                    'pin' =>  $this->__channelObj->wms['crop_config']['pin'],
            );
            
            $callback = array();
            $result = $this->__caller->call(WMS_SALEORDER_INFORM, $params, $callback, $title, 10, $delivery_bn, true, $gateway);
            if($result['rsp'] != 'succ'){
                $error_msg = '京东订单号：'. $package_bn .',通知提前发货失败：';
                $error_msg .= ($result['err_msg'] ? $result['err_msg'] : $result['msg']);
                
                //通知发货失败
                $deliveryObj->update(array('sync_status'=>'11'), array('delivery_id'=>$delivery_id));
                
                //log
                $operLogObj->write_log('delivery_modify@ome', $delivery_id, $error_msg);
                
                return $this->error($error_msg, $msgcode);
            }else{
                //通知发货成功
                $deliveryObj->update(array('sync_status'=>'12'), array('delivery_id'=>$delivery_id));
                
                //log
                $operLogObj->write_log('delivery_modify@ome', $delivery_id, '京东订单号：'. $package_bn .',通知提前发货成功。');
            }
        }
        
        return $this->succ('京东订单号：'. $package_bn .',通知提前发货成功!');
    }
    
    /**
     * 获取发货单上商品的渠道ID
     * 
     * @param array $sdf
     * @return bool
     */
    public function getProductChannelId($sdf)
    {
        if($sdf['wms_channel_id']){
            $this->_channel_id = $sdf['wms_channel_id'];
            return true;
        }
        
        return false;
    }
    
    /**
     * 订单确认收货
     * */
    public function delivery_confirm($sdf)
    {
        $packageObj = app::get('ome')->model('delivery_package');
        
        $keplerLib = kernel::single('ome_reship_kepler');
        
        $order_id = $sdf['order_id'];
        $order_bn = $sdf['order_bn'];
        $branch_bn = $sdf['branch_bn'];
        $msgcode = '';
        
        //渠道ID
        $wms_channel_id = $this->get_warehouse_code($this->__channelObj->wms['channel_id'], $branch_bn);
        
        //获取发货包裹列表
        $skus = array();
        $error_msg = '';
        $packageList = $keplerLib->get_delivery_package($order_id, $skus, $error_msg);
        if(empty($packageList)){
            $error_msg = '没有发货包裹';
            return $this->error($error_msg, $msgcode);
        }
        
        foreach ($packageList as $key => $val)
        {
            $delivery_bn = $val['delivery_bn'];
            
            $title = '发货单号：'. $delivery_bn .'[京东订单号:'. $val['package_bn'] .']同步妥投签收';
            
            //渠道ID(优先使用商品关联的渠道ID)
            $val['wms_channel_id'] = ($val['wms_channel_id'] ? $val['wms_channel_id'] : $wms_channel_id);
            
            //params
            $params = $this->_format_delivery_confirm_params($val);
            
            //request
            $result = $this->__caller->call(WMS_SALEORDER_CONFIRM, $params, null, $title, 10, $delivery_bn);
            if($result['rsp'] != 'succ'){
                return $result;
            }
        }
        
        return $result;
    }
    
    protected function _format_delivery_confirm_params($sdf)
    {
        //渠道ID
        $wms_channel_id = $sdf['wms_channel_id'];
        
        //请求IP地址
        $sdf['remote_addr'] = base_request::get_remote_addr();
        if(empty($sdf['remote_addr'])){
            $sdf['remote_addr'] = kernel::single('base_component_request')->get_server('SERVER_ADDR');
        }
        
        if(empty($sdf['remote_addr'])){
            $sdf['remote_addr'] = '127.0.0.1';
        }
        
        //params
        $params = array(
                'warehouse_code' => $wms_channel_id,
                'trade_code' => $sdf['package_bn'],
                'pin' => $this->__channelObj->wms['crop_config']['pin'], //可以不传
                'client_ip' => $sdf['remote_addr'], //商家操作的客户端IP
                'client_port' => '80', //商家操作的客户端IP
        );
        
        return $params;
    }
    
    /**
     * 读取京东云交易价格
     */
    public function _formatDeliveryWmsPrice($sdf)
    {
        $foreignObj = app::get('console')->model('foreign_sku');
        
        //[格式化]京东云交易货号和价格
        $delivery_items = $sdf['delivery_items'];
        $totalSalePrice = 0;
        foreach ($delivery_items as $k => $v)
        {
            $product_bn = $val['bn'];
            
            //读取WMS货号和价格
            $oldRow = $foreignObj->db_dump(array('inner_sku'=>$v['bn'], 'wms_id' => $this->__channelObj->wms['channel_id']), 'outer_sku,price');
            if ($oldRow) {
                $v['bn'] = $oldRow['outer_sku'];
                $v['price'] = $oldRow['price'];
            }
            
            //销售价
            $v['sale_price'] = bcmul($v['price'], $v['number'], 3);
            
            //items
            $delivery_items[$k] = $v;
            
            //商品总额
            $totalSalePrice = bcadd($v['sale_price'], $totalSalePrice, 3);
        }
        
        //delivery_items
        $sdf['delivery_items'] = $delivery_items;
        
        //所有商品总额
        $sdf['totalSalePrice'] = $totalSalePrice;
        
        return $sdf;
    }
    
    /**
     * 读取平台店铺实付价格
     */
    public function _formatDeliveryShopPrice($sdf)
    {
        $orderCouponDetailObj = app::get('ome')->model('order_coupon');
        
        //order_objects
        $itemList = array();
        $deliveryItemDetailMdl = app::get('ome')->model('delivery_items_detail');
        $tempList = $deliveryItemDetailMdl->getList('*',array('order_obj_id'=>array_column($sdf['order_objects'],'obj_id'),'delivery_id'=>$sdf['delivery_id']));
        //$deliveryItemDetailList = array_column($tempList,null,'oid');
        
        //[PKG捆绑商品]多个相同oid对应不同sku货号的场景
        $deliveryItemDetailList = array();
        foreach($tempList as $key => $val)
        {
            $oid = $val['oid'];
            $order_item_id = $val['order_item_id'];
            
            //pkg
            if($val['item_type'] == 'pkg'){
                $itemKeyName = $oid.'-'.$order_item_id;
                $deliveryItemDetailList[$itemKeyName] = $val;
            }else{
                $deliveryItemDetailList[$oid] = $val;
            }
        }
        
        //查询sku明细实付（京东）
        $orderCouponDetailList = $orderCouponDetailObj->getList('order_id,type,amount,num,oid',array('order_id'=>array_column($sdf['order_objects'],'order_id')));
        if ($orderCouponDetailList) {
            $orderCouponData = $orderCouponDetailObj->getOrderCouponFormatData($orderCouponDetailList,'oid');
        }
        foreach ($sdf['order_objects'] as $objKey => $objVal)
        {
            foreach ($objVal['order_items'] as $itemKey => $itemVal)
            {
                $item_id = isset($itemVal['item_id']) ? $itemVal['item_id'] : $itemKey;
                $product_bn = $itemVal['bn'];
                $price = $itemVal['divide_order_fee'];
                $oid = $itemVal['oid'];
                
                //[兼容]pkg捆绑商品
                $itemKeyName = $oid;
                if($itemVal['item_type'] == 'pkg'){
                    $itemKeyName = $oid.'-'.$item_id;
                }
                
                //只有当商品有oid时才用均摊金额
                $divide_order_fee_status = false;
                if($oid && isset($deliveryItemDetailList[$itemKeyName])){
                    if($deliveryItemDetailList[$itemKeyName]['divide_user_fee'] >= 0){
                        $divide_order_fee_status = true;
                        $price = $deliveryItemDetailList[$itemKeyName]['divide_user_fee'];
                    }
                }
                
                $subsidyFee = 0;
                if ($orderCouponData) {
                    $couponRow = $orderCouponData[$objVal['order_id']][$objVal['oid']];
                    if ($couponRow) {
                        $subsidyFee = ($couponRow['promotion_amount'] + $couponRow['promotion_pay_amount']);
                    }
                }
                $itemList[$product_bn] = array(
                    'nums'              => $itemVal['nums'], //数量
                    'price'             => $itemVal['price'],
                    'pmt_price'         => $itemVal['pmt_price'],
                    'sale_price'        => $itemVal['sale_price'],
                    'divide_order_fee'  => $price, //分摊之后的实付金额
                    'divide_order_fee_status'  => $divide_order_fee_status, //是否重写实付价格
                    'part_mjz_discount' => $itemVal['part_mjz_discount'], //优惠分摊
                    'subsidyFee'        => $subsidyFee,
                );
            }
        }
        
        //delivery_items
        $delivery_items = array();
        $totalSalePrice = 0;
        $pmt_amount = 0;
        $line_i = 0;
        foreach ($sdf['delivery_items'] as $key => $val)
        {
            $product_bn = $val['bn'];
            
            //商品实付金额(取订单明细上的金额)
            $divide_order_fee = $itemList[$product_bn]['divide_order_fee'];
            
            //[订单明细层]分摊之后的实付金额
            $val['sale_price'] = $itemList[$product_bn]['divide_order_fee'];
            
            //计算单价
            $avg_price = bcdiv($val['sale_price'], $val['number'], 2);
            
            //[兼容]拆分SKU数量发货
            if($itemList[$product_bn]['nums'] != $val['number'] && $itemList[$product_bn]['divide_order_fee_status'] == false){
                //计算单价
                $avg_price = bcdiv($val['sale_price'], $itemList[$product_bn]['nums'], 2);
                
                //实付金额
                $val['sale_price'] = bcmul($avg_price, $val['number'], 2);
                
                //发货单上发货商品的实付金额
                $divide_order_fee = $val['sale_price'];
            }
            
            $val['price'] = $avg_price;
            
            //明细行号
            $line_i++;
            
            //价格平摊不平标识
            $not_avg_price = false;
            
            //平摊金额
            if($val['number'] > 1){
                //判断价格是否能摊平
                $temp_sale_price = bcmul($avg_price, $val['number'], 2);
                if(bccomp($temp_sale_price, $val['sale_price'], 2) !== 0){
                    $not_avg_price = true;
                }
            }
            //计算总优惠
            if ($itemList[$product_bn]['subsidyFee'] > 0) {
                $sdf['subsidyFee'][$product_bn]['subsidyFee'] = $itemList[$product_bn]['subsidyFee'] * ($val['number'] / $itemList[$product_bn]['nums']);
            }
            //价格平摊不平,拆分发货数量
            if($not_avg_price){
                $old_sale_price = $val['sale_price'];
                
                //拆分一
                $item_quantity = $val['number'] - 1;
                
                $val['number'] = $item_quantity;
                $val['price'] = $avg_price;
                $val['sale_price'] = bcmul($avg_price, $item_quantity, 2);
                
                $delivery_items[$line_i] = $val;
                
                $line_i++; //明细行号
                
                //拆分二
                $diff_price = $old_sale_price - bcmul($avg_price, $item_quantity, 2);
                $diff_price = number_format($diff_price, 2, '.', '');
                
                $val['number'] = 1;
                $val['price'] = $diff_price;
                $val['sale_price'] = $diff_price;
                
                $delivery_items[$line_i] = $val;
            }else{
                $delivery_items[$line_i] = $val;
            }
            
            //[兼容]没有实付金额
            if(empty($divide_order_fee)){
                $divide_order_fee = 0.00;
            }
            
            $totalSalePrice = bcadd($divide_order_fee, $totalSalePrice, 3);
        }
        
        //delivery_items
        $sdf['delivery_items'] = $delivery_items;
        
        //所有商品总额
        $sdf['totalSalePrice'] = $totalSalePrice;
        
        return $sdf;
    }
    
    /**
     * 读取平台商品原价
     * @todo：支持订单上PKG捆绑商品中绑定的货号 与 普通货号相同的场景;
     * @todo：所以读取sdb_ome_delivery_items_detail发货单商品明细表；
     * 
     * @param array $sdf
     * @return array
     */
    public function _formatDeliveryShopOriginalPrice($sdf)
    {
        $deliveryItemDetailMdl = app::get('ome')->model('delivery_items_detail');
        $orderCouponDetailObj = app::get('ome')->model('order_coupon');
        
        $order_ids = array_column($sdf['order_objects'], 'order_id'); //有订单合并发货的场景
        $delivery_id = $sdf['delivery_id'];
        
        //平台实付金额列表
        $orderCouponData = array();
        $orderCouponDetailList = $orderCouponDetailObj->getList('order_id,type,amount,num,oid', array('order_id'=>$order_ids));
        if ($orderCouponDetailList) {
            $orderCouponData = $orderCouponDetailObj->getOrderCouponFormatData($orderCouponDetailList, 'oid');
        }
        
        //发货单明细
        $dlyItemList = array();
        foreach ($sdf['delivery_items'] as $key => $val)
        {
            $product_bn = $val['bn'];
            $item_nums = $val['number'];
            
            $dlyItemList[$product_bn] = $val;
        }
        
        //variable
        $delivery_items = array();
        $totalSalePrice = 0;
        $totalPromotionAmount = 0;
        $pmt_amount = 0;
        $line_i = 0;
        
        //发货单详细明细
        $tempList = $deliveryItemDetailMdl->getList('*', array('delivery_id'=>$delivery_id));
        foreach($tempList as $key => $val)
        {
            $oid = $val['oid'];
            $product_bn = $val['bn'];
            $order_item_id = $val['order_item_id'];
            $item_type = $val['item_type'];
            $item_quantity = $val['number'];
            
            //货品名称
            $val['product_name'] = $dlyItemList[$product_bn]['product_name'];
            
            //是否赠品
            $val['is_gift'] = ($val['item_type'] == 'gift' ? 'true' : 'false');
            
            //商品实付金额
            $divide_order_fee = $val['divide_order_fee'];
            
            //SKU货品单价
            $origin_amount = $val['origin_amount'];
            
            //SKU货品总价格
            $total_price = $val['total_price'];
            
            //平台商品总优惠
            $total_promotion_amount = $val['total_promotion_amount'];
            
            //均摊不平标识
            $not_avg_price = false;
            
            //是否能均摊
            if($item_quantity > 1){
                //商品实付金额
                $avg_pay_fee = bcdiv($divide_order_fee, $item_quantity, 2);
                $temp_money = bcmul($avg_pay_fee, $item_quantity, 2);
                if(bccomp($temp_money, $divide_order_fee, 2) !== 0){
                    //$not_avg_price = true;
                }
                
                //平台商品总优惠
                $avg_promotion_amount = bcdiv($total_promotion_amount, $item_quantity, 2);
                $temp_money = bcmul($avg_promotion_amount, $item_quantity, 2);
                if(bccomp($temp_money, $total_promotion_amount, 2) !== 0){
                    //$not_avg_price = true;
                }
                
                //货品单价
                $temp_money = bcmul($origin_amount, $item_quantity, 2);
                if(bccomp($temp_money, $total_price, 2) !== 0){
                    $not_avg_price = true;
                }
            }
            
            //明细行号
            $line_i++;
            
            //$val['number'] = $item_quantity;
            $val['price'] = $origin_amount; //单价
            $val['sale_price'] = $divide_order_fee; //实付金额
            $val['discount_price'] = $total_promotion_amount; //SKU商品优惠
            
            //价格平摊不平(拆分为2行显示)
            if($not_avg_price){
                $old_sale_price = $val['sale_price'];
                
                //第一行拆分数量
                $diff_quantity = $item_quantity - 1;
                
                //第一行
                $val['number'] = $diff_quantity;
                $val['price'] = $origin_amount;
                
                $line_sale_price = bcdiv($divide_order_fee, $item_quantity, 2);
                $line_sale_price = bcmul($line_sale_price, $diff_quantity, 2);
                
                $line_discount_price = bcdiv($total_promotion_amount, $item_quantity, 2);
                $line_discount_price = bcmul($line_discount_price, $diff_quantity, 2);
                
                $val['sale_price'] = $line_sale_price;
                $val['discount_price'] = $line_discount_price;
                
                $delivery_items[$line_i] = $val;
                
                //第二行
                $line_i++; //明细行号
                
                $val['number'] = 1;
                $val['sale_price'] = bcsub($divide_order_fee, $line_sale_price, 2);
                $val['discount_price'] = bcsub($total_promotion_amount, $line_discount_price, 2);
                
                //剩余SKU货品单价
                $line_total_price = bcmul($origin_amount, $diff_quantity, 2);
                $val['price'] = bcsub($total_price, $line_total_price, 2);
                
                $delivery_items[$line_i] = $val;
            }else{
                $delivery_items[$line_i] = $val;
            }
            
            //总计
            //$totalSalePrice = bcadd($divide_order_fee, $totalSalePrice, 2);
            $totalPromotionAmount = bcadd($total_promotion_amount, $totalPromotionAmount, 2);
            
            //@todo：产品让传商品总金额(单价*数量)
            $totalSalePrice = bcadd($total_price, $totalSalePrice, 2);
        }
        
        //delivery_items
        $sdf['delivery_items'] = $delivery_items;
        
        //所有商品实付总额
        //@todo：产品让传所有商品总金额(单价*数量)
        $sdf['totalSalePrice'] = $totalSalePrice;
        
        //所有商品总优惠
        $sdf['discount_fee'] = $totalPromotionAmount;
        
        return $sdf;
    }
    
    /**
     * 获取主播分佣信息
     */
    public function _getCommission($sdf)
    {
        $settleObj = app::get('ome')->model('order_settle');
        
        $commissionList = array();
        
        //发货单明细
        $productList = array();
        foreach ($sdf['delivery_items'] as $key => $val)
        {
            $product_bn = $val['bn'];
            
            $productList[$product_bn] = $product_bn;
        }
        
        //订单明细
        $objectList = array();
        $order_ids = array();
        $order_bns = array();
        foreach ($sdf['order_objects'] as $objKey => $objVal)
        {
            $order_id = $objVal['order_id'];
            $order_bn = $objVal['order_bn'];
            $oid = $objVal['oid'];
            
            foreach ($objVal['order_items'] as $itemKey => $itemVal)
            {
                $product_bn = $itemVal['bn'];
                
                //发货单上没有此商品,则跳过
                if(empty($productList[$product_bn])){
                    continue;
                }
                
                //过滤空值
                if(empty($objVal['author_id']) || empty($objVal['author_name'])){
                    continue;
                }
                
                //主播信息
                $commissionList[$oid] = array(
                        'short_id' => $objVal['author_id'], //活动主播ID
                        'author_account' => $objVal['author_name'], //活动主播名
                        'real_comission' => 0, //真实佣金
                        'estimated_comission' => 0, //预估佣金
                        'commission_rate' => 0, //佣金率
                        'oid' => $oid, //子订单号
                );
            }
            
            $order_ids[$order_id] = $order_id;
            $order_bns[$order_bn] = $order_bn;
        }
        
        //[兼容]没有order_id数据
        if(empty($order_ids)){
            $orderObj = app::get('ome')->model('orders');
            $tempList = $orderObj->getList('order_id', array('order_bn'=>$order_bns));
            foreach ($tempList as $key => $val)
            {
                $order_id = $val['order_id'];
                
                $order_ids[$order_id] = $order_id;
            }
        }
        
        //平台订单金额明细
        $tempList = $settleObj->getList('*', array('order_id'=>$order_ids));
        if(empty($tempList)){
            return $commissionList;
        }
        
        //list
        foreach ($tempList as $key => $val)
        {
            $oid = $val['oid'];
            
            //[拆单]发货单上没有此商品,则跳过
            if(empty($commissionList[$oid])){
                continue;
            }
            
            //过滤没有佣金金额
            if($val['real_comission'] <= 0){
                continue;
            }
            
            //佣金信息
            $commissionList[$oid]['real_comission'] = $val['real_comission']; //真实佣金
            $commissionList[$oid]['estimated_comission'] = $val['estimated_comission']; //预估佣金
            $commissionList[$oid]['commission_rate'] = $val['commission_rate']; //佣金率
            
        }
        
        return $commissionList;
    }
    
    /**
     * 获取补贴信息列表
     */
    public function _getSubsidyList($sdf)
    {
        $subsidyList = array();
        
        //订单ID
        $order_ids = array();
        $order_bns = array();
        foreach ($sdf['order_objects'] as $objKey => $objVal)
        {
            $order_id = $objVal['order_id'];
            $order_bn = $objVal['order_bn'];
            
            $order_ids[$order_id] = $order_id;
            $order_bns[$order_bn] = $order_bn;
        }
        
        //[兼容]没有获取到order_id
        if(empty($order_ids) && $order_bns){
            $orderObj = app::get('ome')->model('orders');
            $tempList = $orderObj->getList('order_id', array('order_bn'=>$order_bns));
            foreach ($tempList as $key => $val)
            {
                $order_id = $val['order_id'];
                
                $order_ids[$order_id] = $order_id;
            }
        }
        
        //读取优惠券信息
        $pmtObj = app::get('ome')->model('order_pmt');
        $tempList = $pmtObj->getList('*', array('order_id'=>$order_ids));
        if($tempList){
            foreach ($tempList as $key => $val)
            {
                if(empty($val['coupon_id'])){
                    continue;
                }
                
                //补贴比例
                //$subsidyRatio = bcdiv($val['pmt_amount'], $sdf['total_amount'], 3);
                $subsidyRatio = 0; //跟京东开普勒确认传0
                
                //data
                $subsidyList[] = array(
                        'subsidyFee' => $val['pmt_amount'], //补贴金额
                        'subsidyRatio' => $subsidyRatio,
                        'subsidyId' => (string)$val['coupon_id'], //补贴id
                );
            }
        }
        
        //[兼容]没有优惠默认传空
        if(empty($subsidyList)){
            $subsidyList[] = array(
                    'subsidyFee' => 0,
                    'subsidyRatio' => 0,
                    'subsidyId' => '0',
            );
        }
        
        return $subsidyList;
    }
    
    /**
     * 获取平台订单信息
     */
    public function _getPlatformOrderInfo($sdf)
    {
        $platformOrderInfo = array();
        
        $order_bn = $sdf['order_bn']; //合并发货单有多个订单号会以','逗号分隔
        $order_createtime = ($sdf['platform_createtime'] ? $sdf['platform_createtime'] : $sdf['pay_time']); //顾客下单时间
        $order_pay_time = ($sdf['platform_paytime'] ? $sdf['platform_paytime'] : $sdf['pay_time']); //顾客付款时间
        
        //平台店铺的shop_id
        $platform_shop_id = trim($sdf['platform_shop_id']);
        
        //oaid
        if ($sdf['encrypt_source_data']['oaid']) {
            $sdf['oaid'] = $sdf['encrypt_source_data']['oaid'];
        }
        
        //check
        if(empty($sdf['order_objects']) || empty($sdf['delivery_items'])){
            return $platformOrderInfo;
        }
        
        //order_objects
        $productList = array();
        foreach ($sdf['order_objects'] as $objKey => $objVal)
        {
            $oid = $objVal['oid'];
            foreach ($objVal['order_items'] as $itemKey => $itemVal)
            {
                $product_id = $itemVal['product_id'];
                
                $productList[$product_id] = array(
                        'item_id' => $itemVal['item_id'],
                        'obj_id' => $itemVal['obj_id'],
                        'product_id' => $itemVal['product_id'],
                        'bn' => $itemVal['bn'],
                        'oid' => $oid,
                        'order_bn' => $objVal['order_bn'],
                );
            }
        }
        
        //delivery_items
        $oidList = array();
        foreach ($sdf['delivery_items'] as $itemKey => $itemVal)
        {
            $product_id = $itemVal['product_id'];
            
            $productInfo = $productList[$product_id];
            if(empty($productInfo)){
                continue;
            }
            
            $oid = $productInfo['oid'];
            
            //oid
            $oidList[$oid] = $oid;
        }
        
        //过滤抖音订单A字母
        $order_bn = str_replace('A', '', $order_bn);
        
        //format
        $oids = implode(',', $oidList);
        $platformOrderInfo = array(
                'outPlatformParentOrderId' => $order_bn, //第三方平台父单号
                'outPlatformOrderId' => $oids, //第三方平台子单号(多个单号以','逗号分隔)
                'outPlatformCreateTime' => $order_createtime, //第三方平台订单创建时间（必传）
                'outPlatformPayTime' => $order_pay_time, //第三方平台订单支付时间
                'outPlatformShopId' => $platform_shop_id, //对应矩阵字段名：outPlatformShopId
        );
        
        //三方平台oaId，来源：三方渠道
        if($sdf['oaid']){
            $platformOrderInfo['oaId'] = $sdf['oaid'];
        }
        
        return $platformOrderInfo;
    }
}
