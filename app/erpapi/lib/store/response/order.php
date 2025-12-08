<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_order extends erpapi_store_response_abstract
{
    public $status     = array('TRADE_ACTIVE' => 'active', 'TRADE_CLOSED' => 'dead', 'TRADE_FINISHED' => 'finish');
    public $pay_status = array(
        'PAY_NO'        => 0,
        'PAY_FINISH'    => 1,
        'PAY_TO_MEDIUM' => 2,
        'PAY_PART'      => 3,
        'REFUND_PART'   => 4,
        'REFUND_ALL'    => 5,
        'REFUNDING'     => 6,
    );
    public $ship_status = array(
        'SHIP_NO'      => 0,
        'SHIP_FINISH'  => 1,
        'SHIP_PREPARE' => 1,
        'SHIP_PART'    => 2,
        'RESHIP_PART'  => 3,
        'RESHIP_ALL'   => 4,
    );

    /**
     * 添加
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function add($params)
    {
        // 参数校验
         $this->__apilog['title']       = $this->__channelObj->store['name'] .'('.$params['store_bn'].')订单' . $params['tid'].'|'.microtime(true);
        
        $this->__apilog['original_bn'] = $params['tid'];

        $store_bn = $params['store_bn'];

        if (empty($store_bn)) {

            $this->__apilog['result']['msg'] = "下单门店编码不可以为空";
            return false;
        }

        $shops_detail = app::get('ome')->model('shop')->dump(array('shop_bn' => $store_bn));
        if (!$shops_detail) {
            $this->__apilog['result']['msg'] = $store_bn . ":门店不存在";
            return false;
        }

        $store_detail = app::get('o2o')->model('store')->dump(['store_bn' => $store_bn], 'server_id');


        if ($store_detail['server_id'] != $this->__channelObj->store['server_id']) {
            $this->__apilog['result']['msg'] = "门店服务端配置异常，请联系OMS管理员核查";
            //return false;
        }

      
      
        if($params['is_cod'] == 'true'){
            if($params['total_trade_fee']<=0){
                $this->__apilog['result']['msg'] = "货到付款订单,订单总金额为0不收";
                return false;
            }
        }
        
        if (is_string($params['orders'])) {
            $params['orders'] = json_decode($params['orders'], true);
        }
       
        // 维修订单 end

        //加判断货号不存在不收
        foreach($params['orders'] as $k=>$v){

            if($v['barcode']){
                $bn = kernel::single('material_codebase')->getBnBybarcode($v['barcode']);
                $v['bn'] =$bn;
                if(!$bn){
                    $this->__apilog['result']['msg'] = $v['barcode'].":条码不存在";
                    return false;
                }
                $params['orders'][$k]['bn'] = $bn;

            }

            foreach ($v['order_items'] as $i_k => $i_v) {
                if($i_v['barcode']){
                    $bn = kernel::single('material_codebase')->getBnBybarcode($i_v['barcode']);
                    if(!$bn){
                        $this->__apilog['result']['msg'] = $i_v['barcode'].":条码不存在";
                        return false;
                    }
                    $params['orders'][$k]['order_items'][$i_k]['bn'] = $bn;
                }
            }
            if( !isset($v['bn']) || $v['bn']==''){
                $this->__apilog['result']['msg'] = "请检查orders行,bn不可为空";
                return false;
            }

            //转换code
        }

       
        $this->_dealSavePos($params);

      

        
        $data = $this->__formatData($params);

        $data['shop_type'] = $shops_detail['node_type'];
        $data['node_id']   = $shops_detail['node_id'];
       
        return $data;
    }

    private function __formatData($params)
    {
        if (is_string($params['orders'])) {
            $params['orders'] = json_decode($params['orders'], true);
        }
        if (is_string($params['payment_lists'])) {
            $params['payment_lists'] = json_decode($params['payment_lists'], true);
        }
        if (is_string($params['promotion_details'])) {
            $params['promotion_details'] = json_decode($params['promotion_details'], true);
        }

        $order_sdf['node_id']    = $params['node_id']; //门店编码

        $order_sdf['order_bn']   = $params['tid']; //交易编号
        $order_sdf['title']      = $params['title']; //交易标题
        $order_sdf['createtime'] = $params['created']; //交易创建时间
        $order_sdf['lastmodify'] = $params['modified'] ? $params['modified'] : $params['modified']; //交易最后更新时间
        // custom 暂时统一为active
        $order_sdf['status']                  = 'active';
        $order_sdf['source_status']           =$params['status'];
        $order_sdf['pay_status']              = $this->pay_status[$params['pay_status']]; //交易支付状态
        $order_sdf['ship_status']             = $this->ship_status[$params['ship_status']]; //交易物流状态
        $order_sdf['is_delivery']             = $params['is_delivery']; //是否实体配送
        $order_sdf['is_tax']                  = $params['has_invoice']; //是否开发票
        $order_sdf['tax_title']               = $params['invoice_title']; //发票抬头
        $order_sdf['cost_tax']                = $params['invoice_fee']; //发票金额
        $order_sdf['value_added_tax_invoice'] = $params['value_added_tax_invoice']; //是否是增值税发票
        $order_sdf['invoice_bank_account']    = $params['invoice_bank_account']; //银行账户
        $order_sdf['invoice_address']         = $params['invoice_buyer_address']; //企业开票地址
        $order_sdf['invoice_phone']           = $params['invoice_phone']; //收票人电话
        $order_sdf['invoice_receiver_mobile'] = $params['invoice_receiver_mobile'];
        $order_sdf['invoice_receiver_email']  = $params['invoice_receiver_email'];

        $order_sdf['invoice_receiver_name']   = $params['invoice_receiver_name'];//开票联系人

        $order_sdf['invoice_receiver_addr']   = $params['invoice_receiver_addr'];//收票人地址

        $order_sdf['invoice_bank_name']       = $params['invoice_bank_name']; //开户银行
        $order_sdf['invoice_kind']            = $params['invoice_kind']; //开票类型
        $order_sdf['invoice_desc']            = $params['invoice_desc']; //发票内容
        $order_sdf['payer_register_no']       = $params['payer_register_no']; //纳税人识别号
        $order_sdf['cost_item']               = $params['total_goods_fee']; //商品总额
        $order_sdf['total_amount']            = $params['total_trade_fee']; //交易应付总额
        $order_sdf['discount']                = $params['discount_fee']; //折扣优惠金额
        $order_sdf['pmt_goods']               = $params['goods_discount_fee']; //商品优惠金额
        $order_sdf['pmt_order']               = $params['orders_discount_fee']; //订单优惠金额
        $order_sdf['payed']                   = $params['payed_fee']; //已支付金额
        $order_sdf['currency']                = $params['currency']; //当前交易选择的货币类型
        $order_sdf['cur_rate']                = $params['currency_rate']; //当前货别汇率
        $order_sdf['cur_amount']              = $params['total_currency_fee']; //当前货币订单总额
        $order_sdf['score_g']                 = $params['buyer_obtain_point_fee']; //买家获得积分,返点的积分
        $order_sdf['score_u']                 = $params['point_fee']; //买家使用积分
        $order_sdf['pay_bn']                  = $params['payment_tid']; //支付方式ID
        $order_sdf['order_limit_time']        = $params['trade_valid_time']; //订单失效时间
        $order_sdf['end_time']                = $params['end_time']; //交易成功时间
        $order_sdf['mark_text']               = $params['trade_memo']; //交易备注
        $order_sdf['custom_mark']             = $params['buyer_message'] ? $params['buyer_message'] : $params['buyer_memo']; //买家备注

        $order_sdf['custom_mark'] = kernel::single('ome_order_func')->filterEmoji($order_sdf['custom_mark']);

        $order_sdf['buyer_flag']              = $params['buyer_flag']; //买家备注旗帜
        $order_sdf['t_type']                  = !$params['tradetype'] ? 'fixed' : $params['tradetype']; //交易类型
        $order_sdf['itemnum']                 = $params['orders_number']; //子订单数量
        $order_sdf['weight']                  = $params['total_weight']; //该笔交易的商品总重量
        $order_sdf['paytime']                 = $params['pay_time']; //付款时间

        $order_sdf['passby_account']          = $params['passby_account'];  

        $order_sdf['pos_machine_code']        = $params['pos_machine_code'];  
        $order_sdf['movement_code']           = $params['movement_code'];  
        $order_sdf['cs_order_no'] = $params['cs_order_no'];
        $order_sdf['md_guider']               = $params['md_guider'];
        $order_sdf['relate_order_bn']         = $params['relate_order_bn'];
        //订单优惠方案信息  begin
        if ($params['promotion_details']) {
            $tmp_pmt_detail = $params['promotion_details'];
        }
        $order_sdf['order_source'] = $params['order_source'];
        $order_sdf['order_sort']   = $params['order_sort'];
        $order_sdf['pmt_detail']   = array();
        $order_sdf['other_list']   = $params['other_list'];
        $order_sdf['tariff']       = $params['tariff'];
        $k_count                   = 0;

        if ($tmp_pmt_detail) {
            foreach ((array) $tmp_pmt_detail as $k => $v) {
                $order_sdf['pmt_detail'][$k]['pmt_amount']   = $v['promotion_fee'];
                $order_sdf['pmt_detail'][$k]['pmt_describe'] = $v['promotion_name'];
                $order_sdf['pmt_detail'][$k]['pmt_id']       = $v['coupon_id'];
                $order_sdf['pmt_detail'][$k]['promotion_id'] = $v['promotion_id'];
                $order_sdf['pmt_detail'][$k]['pmt_memo']     = $v['promotion_desc'];
                $order_sdf['pmt_detail'][$k]['discount_code']= $v['discount_code'];
                $order_sdf['pmt_detail'][$k]['oid']          = $v['oid'];
                $order_sdf['pmt_detail'][$k]['quantity']     = $v['quantity'];

            }
        }

        //订单优惠方案信息  end

        //配送信息 begin
        $shipping['is_cod']        = $params['is_cod']; //是否货到付款
        $shipping['shipping_name'] = $params['shipping_type'];
        $shipping['cost_shipping'] = $params['shipping_fee'];
        $shipping['is_protect']    = $params['is_protect'];
        $shipping['cost_protect']  = $params['protect_fee'];
        $shipping['shipping_id']   = $params['shipping_tid'];
        //配送信息 end
        $order_sdf['shipping'] = $shipping;

        // 收货人信息 begin
        $consignee['name']          = $params['receiver_name'];
        $consignee['area_state']    = $params['receiver_state'];
        $consignee['area_city']     = $params['receiver_city'];
        $consignee['area_district'] = $params['receiver_district'];
        $consignee['addr']          = $params['receiver_address'] ? $params['receiver_address'] : '门店销售';
        $consignee['zip']           = $params['receiver_zip'];
        $consignee['telephone']     = $params['receiver_phone'];
        $consignee['mobile']        = $params['receiver_mobile'];
        $consignee['email']         = $params['receiver_email'];
        $consignee['r_time']        = $params['receiver_time'];
        //收货人信息 end
        $order_sdf['consignee'] = $consignee;

        //买家会员信息 begin
        $member_info['buyer_id']      = $params['buyer_id']; //买家id
        $member_info['uname']         = $params['buyer_uname']; //卡号
        $member_info['name']          = $params['buyer_name'];
        $member_info['alipay_no']     = $params['buyer_alipay_no'];
        $member_info['area_state']    = $params['buyer_state'];
        $member_info['area_city']     = $params['buyer_city'];
        $member_info['area_district'] = $params['buyer_district'];
        $member_info['addr']          = $params['buyer_address'];
        $member_info['mobile']        = $params['buyer_mobile'];
        $member_info['tel']           = $params['buyer_phone'];
        $member_info['email']         = $params['buyer_email'];
        $member_info['zip']           = $params['buyer_zip'];

        $member_info['buyer_rate'] = $params['buyer_rate']; //买家是否已评价
        //买家会员信息 end
        $order_sdf['member_info'] = $member_info;

        $order_sdf['seller_flag'] = $params['seller_flag']; //卖家备注旗帜

        //结算
      
     
        //支付方式信息 begin
        $payinfo['payment_tid']  = $params['payment_tid']; //支付方式ID
        $payinfo['pay_name']     = $params['payment_type'];
        $payinfo['cost_payment'] = $params['pay_cost']; //支付手续费
        $order_sdf['payinfo']    = $payinfo;
        //支付单信息  新版本
        if ($params['payment_lists']) {
            foreach ((array) $params['payment_lists'] as $p_k => $p_v) {
                $payments[$p_k]['trade_no']    = $p_v['payment_id'] ? $p_v['payment_id'] : '';
                $payments[$p_k]['money']       = isset($p_v['pay_fee']) ? $p_v['pay_fee'] : $p_v['payed_fee'];
                $payments[$p_k]['pay_time']    = $p_v['pay_time'];
                $payments[$p_k]['account']     = $p_v['seller_account'];
                $payments[$p_k]['bank']        = $p_v['seller_bank'];
                $payments[$p_k]['pay_bn']      = $p_v['payment_code'];
                $payments[$p_k]['paycost']     = $p_v['paycost'];
                $payments[$p_k]['pay_account'] = $p_v['buyer_account'];
                $payments[$p_k]['paymethod']   = $p_v['payment_name'];
                $payments[$p_k]['outer_no']    = $p_v['outer_no'];
                $payments[$p_k]['memo']        = $p_v['memo'];
            }
        }
        $order_sdf['payments'] = $payments;
        //支付方式信息 end

        #门店信息
        $order_sdf['o2o_info'] = $params['o2o_info'];

        // shipping_type 门店配送 & 门店自提
        $order_sdf['store_dly_type'] = $params['shipping_type'] ? $params['shipping_type'] == '门店配送' ? 'o2o_ship' : 'o2o_pickup' : 'o2o_pickup'; //门店发货模式

        $order_sdf['order_type'] = 'offline';

        $order_sdf['commission_fee'] = $params['commission_fee']; //交易佣金
        $order_sdf['consign_time']   = $params['consign_time']; //卖家发货时间

        // 维修单先仓
        if ($params['order_sort'] == 'maintain') {
            // 重置门店仓，进行系统路由
            $branch = app::get('ome')->model('branch')->dump([
                'branch_bn'     => $order_sdf['consignee']['name'],
                'storage_code'  => $order_sdf['consignee']['name'],
                'type'          => 'maintain',
            ], 'branch_id,branch_bn');

            foreach ($params['orders'] as $o_k => $o_v) {
                $params['orders'][$o_k]['store_code'] = $branch ? $branch['branch_bn'] : '';
            }

            $order_sdf['order_source'] = 'R';
            if(!$branch){
                //$order_sdf['is_delivery'] = 'false';
            }
        }

        //订单商品结构数组信息
        $order_objects = array();

        foreach ($params['orders'] as $o_k => $o_v) {
            $order_objects[$o_k]['oid']           = $o_v['oid']; //子订单编号
            $order_objects[$o_k]['obj_type']      = $o_v['type']; //订单类型
            $order_objects[$o_k]['shop_goods_id'] = $o_v['iid']; //商品ID
            $order_objects[$o_k]['obj_alias']     = $o_v['type_alias'];
            $order_objects[$o_k]['bn']            = $o_v['bn'];
            $order_objects[$o_k]['name']          = $o_v['title']; //商品名称
            $order_objects[$o_k]['price']         = $o_v['total_order_fee'] / $o_v['items_num']; //原始单价
            $order_objects[$o_k]['amount']        = $o_v['total_order_fee']; //订单金额
            $order_objects[$o_k]['pmt_price']     = $o_v['discount_fee']; //订单优惠金额
            $order_objects[$o_k]['sale_price']    = $o_v['sale_price'];
            $order_objects[$o_k]['quantity']      = $o_v['items_num']; //订单下商品数量
            $order_objects[$o_k]['weight']        = $o_v['weight']; //订单货品的总重量
            $order_objects[$o_k]['score']         = 0; //积分
            $order_objects[$o_k]['status']        = $this->status[$o_v['status']]; //订单状态
            $order_objects[$o_k]['ship_status']   = in_array($o_v['ship_status'], array('0', '1')) ? $o_v['ship_status'] : '0'; //发货状态
            //$order_objects[$o_k]['ship_status'] = $this->ship_status[$o_v['ship_status']];//发货状态
            $order_objects[$o_k]['pay_status']        = $this->pay_status[$o_v['pay_status']]; //支付状态
            $order_objects[$o_k]['consign_time']      = $this->pay_status[$o_v['consign_time']]; //发货时间
            $order_objects[$o_k]['is_oversold']       = $o_v['is_oversold']; //淘宝超卖标记
            $order_objects[$o_k]['md_guider']         = $o_v['md_guider']; //导购员
            $order_objects[$o_k]['part_mjz_discount'] = $o_v['part_mjz_discount']; //均摊优惠
            $order_objects[$o_k]['divide_order_fee']  = $o_v['divide_order_fee']; //均摊后的实付

            if($o_v['estimate_con_time']){
                $estimate_con_time = kernel::single('ome_func')->date2time($o_v['estimate_con_time']);
                $order_objects[$o_k]['estimate_con_time'] = $estimate_con_time;
            }
         
           
            // 如果状态是finish,则附加store_code
            if ($order_objects[$o_k]['status'] == 'finish') {
                $order_objects[$o_k]['store_code'] = $o_v['store_code']; //门店编码
                $order_sdf['order_bool_type'] = ome_order_bool_type::__O2OPICK_CODE;
            }

            $order_items = array();

            $total_pmt_price = 0;

            foreach ($o_v['order_items'] as $i_k => $i_v) {
                $order_items[$i_k]['bn']              = $i_v['bn']; //货品编码
                $order_items[$i_k]['name']            = $i_v['name']; //货品名称
                $order_items[$i_k]['shop_goods_id']   = $i_v['iid']; //sku所属商品id
                $order_items[$i_k]['shop_product_id'] = $i_v['sku_id']; //商品的最小库存单位Sku的id
                $order_items[$i_k]['weight']          = $i_v['weight']; //sku重量
                $order_items[$i_k]['score']           = $i_v['score'];
                $order_items[$i_k]['cost']            = $i_v['cost'];
                $order_items[$i_k]['price']           = $i_v['price'];
                $order_items[$i_k]['sale_price']      = $i_v['sale_price'];
                $order_items[$i_k]['amount']          = $i_v['total_item_fee'];
                $order_items[$i_k]['quantity']        = $i_v['num'];
                $order_items[$i_k]['sendnum']         = $i_v['sendnum'];
                $order_items[$i_k]['item_type']       = $i_v['item_type'];
                $order_items[$i_k]['item_status']     = $i_v['item_status'];
                $order_items[$i_k]['promotion_id']    = $i_v['promotion_id'];
                $order_items[$i_k]['pmt_price']       = $i_v['discount_fee'];
                $total_pmt_price += $i_v['discount_fee'];
                $order_items[$i_k]['part_mjz_discount'] = $i_v['part_mjz_discount']; //均摊优惠
                $order_items[$i_k]['divide_order_fee']  = $i_v['divide_order_fee']; //均摊后的实付
                $order_items[$i_k]['movement_code']     = $i_v['movement_code'];
                if($i_v['sn_list']){
                    $sn_list = is_array($i_v['sn_list']) ? json_encode($i_v['sn_list']) :$i_v['sn_list'];
                    $order_items[$i_k]['sn_list']  = $sn_list; //均摊后的实付
                }
            }
            $order_objects[$o_k]['order_items'] = $order_items;
            $order_objects[$o_k]['pmt_price']   = $o_v['discount_fee'] - $total_pmt_price;
        }

        $order_sdf['order_objects'] = $order_objects;
        $order_sdf['invoice_status'] = $params['invoice_status'];
        return $order_sdf;
    }

    /**
     * refundagree
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function refundagree($params)
    {
        $this->__apilog['title']       = '检查订单是否可退款';
        $this->__apilog['original_bn'] = $params['order_bn'];
        return $params;
    }

    /**
     * returnagree
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function returnagree($params)
    {

        $this->__apilog['title']       = '检查订单是否可退货';
        $this->__apilog['original_bn'] = $params['order_bn'];
        return $params;
    }

    /**
     * _dealSavePos
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function _dealSavePos($params)
    {

        $orderMdl = app::get('pos')->model('orders');

        $orders    = $orderMdl->db_dump(array('tid' => $params['tid'], 'store_bn' => $params['store_bn']), 'id');
        $orderData = [

            'tid'                 => $params['tid'],
            'store_bn'            => $params['store_bn'],
            'title'               => $params['title'],
            'created'             => strtotime($params['created']),
            'modified'            => strtotime($params['modified']),
            'status'              => $params['status'],
            'pay_status'          => $params['pay_status'],
            'ship_status'         => $params['ship_status'],
            'total_goods_fee'     => (float)$params['total_goods_fee'],
            'total_trade_fee'     => (float)$params['total_trade_fee'],
            'discount_fee'        => (float)$params['discount_fee'],
            'goods_discount_fee'  => (float)$params['goods_discount_fee'],
            'orders_discount_fee' => (float)$params['orders_discount_fee'],
            'payed_fee'           => (float)$params['payed_fee'],
            'tradetype'           => $params['tradetype'],
            'orders_number'       => $params['orders_number'],
            'params'              => json_encode($params),
            'source'              => $this->__channelObj->store['node_type'],

        ];

        if ($orders) {

            $filter = array('id' => $orders['id']);
            $id     = $orderMdl->update($orderData, $filter);
        } else {
            $id = $orderMdl->insert($orderData);
        }

        if (!$id) {
            return [false, ['msg' => 'POS订单数据保存失败']];
        }
    }
    
    /**
     * 获取Materials
     * @param mixed $material_bns material_bns
     * @return mixed 返回结果
     */
    public function getMaterials($material_bns){
        $materialMdl    = app::get('material')->model('basic_material');
        $materials = $materialMdl->getlist('bm_id',array('material_bn'=>$material_bns));
        $bm_ids = array_column($materials,'bm_id');
        $extObj = app::get('material')->model('basic_material_ext');
        $materialLists = $extObj->getList('cat_id,bm_id',array('bm_id' => $bm_ids));
        $type_ids=array_unique(array_column($materialLists,'cat_id'));

        $typeObj = app::get('ome')->model('goods_type');
        $types = $typeObj->getList('name,type_id',array('type_id' => $type_ids,'name'=>'R'));

        if($types){
            return true;
        }
        return false;
    }
}
