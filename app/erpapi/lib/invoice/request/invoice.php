<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 电子发票请求接口函数实现类
 *
 * @author xiayuanjun<xiayuanjun@shopex.cn>
 * @version 0.1
 */
class erpapi_invoice_request_invoice extends erpapi_invoice_request_abstract
{
    /**
     * 开票推送是否异步请求
     * @var bool
     */
    protected $_isCreateAsync = true;

    /**
     * 开票
     * 
     * @param array $sdf
     * @return array
     */

    public function create($sdf)
    {
        $apiname = $this->get_create_apiname();

        $params = $this->get_create_params($sdf);

        if (!$params) {
            return array('rsp'=>'fail','err_msg'=>'税额、含税金额、不含税金额错误');
        }

        $callbackParams = $this->_get_create_callback_params($sdf);

        if($this->__isCreateAsync){
            $callback = array(
                'class' => get_class($this),
                'method' => 'create_callback',
                'params' => $callbackParams,
            );
        }else{
            $callback = array();
        }

        $gateway = '';
        if ($sdf['is_encrypt']) {
            $params['order_bns']        = $sdf['order_bns'];
            $params['s_node_type']      = $sdf['s_node_type'];
            $params['s_node_id']        = $sdf['s_node_id'];
            $params['ship_company_tel'] = $sdf['ship_company_tel'];
            $params['ship_bank']        = $sdf['ship_bank'];
            $params['ship_bank_no']     = $sdf['ship_bank_no'];
            $params['ship_tel']         = $sdf['ship_tel'];

            $gateway = $sdf['s_node_type'];
        }

        $rs = $this->__caller->call($apiname,$params,$callback,'电子开蓝票',30,$sdf["order_bn"],true,$gateway);
        if ($rs['rsp'] == 'fail' && $sdf['id']) {
            $invOrderMdl = app::get('invoice')->model('order');
            $optMdl      = app::get('ome')->model('operation_log');
    
            $invOrderMdl->update(array('sync'=>'2','is_status'=>'0'),array('id'=>$sdf['id']));
            $optMdl->write_log('invoice_billing@invoice', $sdf['id'], sprintf('开票失败：%s',$rs['msg']));
        }
        // 同步时模拟callback
        if($this->__isCreateAsync){
            $this->create_callback($rs, $callbackParams);
        }

        return $rs;
    }

    protected function get_create_apiname()
    {
        return '';
    }

    /**
     * 组装开票参数
     * 
     * @return void
     * @author 
     */
    protected function get_create_params($sdf)
    {
        kernel::single('ome_func')->split_area($sdf['ship_area']);

        if ($sdf['einvoice_type'] == 'blue' && $sdf['tid']) {
            $sdf['remarks'] .= ' 订单号:'.$sdf['tid'];
        }

        $params = array(
            'payer_register_no'   => $sdf['ship_tax'], # 购方税号 企业要填，个人可为空    必须 
            'payer_name'          => $sdf['title'], # 购方名称
            'payer_phone'         => substr($sdf['ship_tel'],0,20), # 购方手机(开票成功会短信提醒购方)        必须
            'payer_telephone'     => $sdf['ship_company_tel'], # 购方电话                            非必须
            'payer_address'       => $sdf['ship_company_addr'], # 购方地址  企业要填，个人可为空
            'payer_bankaccount'   => $sdf['ship_bank'].$sdf['ship_bank_no'], # 购方银行账号 企业要填，个人可为空
            'tid'                 => $sdf['order_bn'], # 订单号 每个企业唯一
            'invoice_time'        => date('Y-m-d H:i:s'), # 开票时间
            'payee_operator'      => $sdf['payee_operator'], # 开票员
            'invoice_type'        => $sdf['einvoice_type']=='blue'?1:2, # 开票类型:1,正票;2,红票
            'invoice_memo'        => $sdf['remarks'], # 冲红时，必须在备注中注明“对应正数发票代码:XXXXXXXXX号码:YYYYYYYY”文案，其中“X”为发票代码，“Y”为发票号码，否则接口会自动添加该文案
            'payee_receiver'      => $sdf['payee_receiver'], # 收款人 可为空
            'payee_checker'       => $sdf['payee_checker'], # 复核人 可为空
            'normal_invoice_code' => str_pad($sdf['invoice_code'], 12, "0", STR_PAD_LEFT), # 红票必填，不满12 位请左补 0   ,  对应蓝票发票代码
            'normal_invoice_no'   => str_pad($sdf['invoice_no'], 8, "0", STR_PAD_LEFT), # 红票必填，不满8 位请左补 0  ,  对应蓝票发票号码 
            'payee_bankaccount'   => $sdf['bank'].$sdf['bank_no'], # 销方银行账号
            'payee_phone'         => $sdf['telephone'], # 销方电话  新增                       必须
            'payee_address'       => $sdf['address'], # 销方地址                           必须
            'payee_register_no'   => $sdf['tax_no'], # 销方税号                       必须 
            'dkbz'                => 0, # 代开标志:0 非代开;1代开。代开蓝票备注文案要求包含：代开企业税号:***,代开企业名称:***；代开红票备注文案要求：对应正数发票代码:***号码:***代开企业税号 :*** 代 开 企 业 名称:***。
            'dept_id'             => '', # 部门门店 id（诺诺系统中的 id） 非必须
            'clerk_id'            => '', # 开票员 id（诺诺系统中的 id） 非必须
            'invoice_line'        => 'p', # 发票种类，p 电子增值税普通发票，c 增值税普通发票(纸票)，s增值税专用发票 默认为电票 p  非必须
            'cpybz'               => 0, # 成品油标志：0 非成品油，1 成品油， 默认为0非成品 油  非必须
            'tsfs'                => 1, # 推 送 方 式 :-1, 不 推送;0,邮箱;1,手机(默认);2,邮箱、手机
            'email'               => '', # 推送邮箱（tsfs 为 0或 2 时，此项为必填）
            'qdbz'                => 0, # 清单标志:0,根据项目名称数，自动产生清单;1,将项目信息打印至清单
            'qdxmmc'              => '', # 清单项目名称:打印清单时对应发票票面项目名称，注意：税总要求清单项目名称90为（详见销货清单
            'jinshui_api_key'     => $this->__channelObj->channel['extend_data']['jinshui_api_key'],
        );

        $detail = array();
        foreach ($sdf['items'] as $value) {
            $d = array(
                'item_name'      => $value['spmc'], # 如 FPHXZ=1，则此商品行为折扣行，此版本折扣行不允许多行折扣，折扣行必须紧邻被折扣行，项目名称必须与被折扣行一致   商品名称
                'quantity'       => $value['spsl'], # 冲红时项目数量为负数,     数量；数量、单价必须都不填，或者都必16填，不可只填一个；当数量、单价都不填时，不含税金额、税额、含税金额都必填
                'price'          => $value['spdj'], # 冲红时项目单价为正数    单价；数量、单价必须都不填，或者都必填，不可只填一个；当数量、单价都不填时，不含税金额、税额、含税金额都必填
                'hsbz'           => '0', # 单价含税标志，0:不含税,1:含税
                'tax_rate'       => $value['sl'], # 税率
                'specification'  => $value['ggxh'], # 规格型号
                'unit'           => $value['dw'], # 单位 非必须
                'item_no'        => $value['spbm'], # 商品编码      签订免责协 议客户可不传入，由接口进行匹配，如对接口速度敏感的企业，建议传入该字段
                'zsbm'           => $value['fpmxxh'], # 自行编码
                'row_type'       => $value['fphxz'],# # 发票行性质:0,正常行;1,折扣行;2,被折扣行
                'yhzcbs'         => $value['yhzcbs'], #  优惠政策标识:0,不使用;1,使用
                'zzstsgl'        => $value['zzstsgl'],# 当 yhzcbs 为 1时，此项必填  增值税特殊管理，如：即征即退、免税、简易征收 等
                'zero_rate_flag' => $value['lslbs'], # 零税率标识:空,非零税率;1,免税;2,不征税;3,普通零税率
                'kce'            => '', # 扣除额，小数点后两位。差额征收的发票目前只支持一行明细。不含税差额 = 不含税金额 - 扣除额；税额 = 不含税差额*税率
                'taxfree_amount' => $value['je'], # 精确到小数点后面两位，红票为负。不含税金额、税额、含税金额任何一个不传时，会根据传入的单价，数量进行计算，可能和实际数值存在误差，建议都传入  不含税金额
                'tax'            => $value['se'], # 精确到小数点后面两位，红票为负。不含税金额、税额、含税金额任何一个不传时，会根据传入的单价，数量进行计算，可能和实际数值存在误差，建议都传入    税额，[不含税金额]* [税率] = [税额]；税额允许误差为0.06
                'amount'         => $value['jshj'], # 精确到小数点后面两位，红票为负。不含税金额、税额、含税金额任何一个不传时，会根据传入的单价，数量进行计算，可能和实际数值存在误差，建议都传入
            );

            if (!$d['tax'] || !$d['taxfree_amount'] || !$d['amount'] || 0 != bccomp($d['amount'], bcadd($d['tax'],$d['taxfree_amount'],2), 2)) {
                return false;
            }


            $detail[] = $d;
        }

        $params['detail'] = json_encode($detail);

        return $params;
    }

    /**
     * 创建_callback
     * @param mixed $response response
     * @param mixed $callback_params 参数
     * @return mixed 返回值
     */
    public function create_callback($response, $callback_params){
        $rsp     = $response['rsp'];
        $err_msg = $response['err_msg'];
        $data    = $response['data'];
        $msg_id  = $response['msg_id'];
        $res     = $response['res'];

        $id = $callback_params['id'];

        $invOrderMdl = app::get('invoice')->model('order');
        $optMdl      = app::get('ome')->model('operation_log');

        // 开票失败,置为未开票
        if ($rsp != 'succ') {
            $invOrderMdl->update(array('sync'=>'2','is_status'=>'0'),array('id'=>$id));

            $optMdl->write_log('invoice_billing@invoice', $id, sprintf('开票失败：%s',$err_msg));

            return $this->callback($response, $callback_params);
        }

        $data  =  @json_decode($data, true);

        // 开票成功
        $updateData = array(
            'sync'      => '1',
            'is_status' => '1',
            'amount'    => $callback_params['amount'],      // 价税合计，就是开票金额
            'cost_tax'  => $callback_params['cost_tax'],    // 价金
            'update_time' => time(),
        );
        $invOrderMdl->update($updateData,array('id'=>$id));

        // 更新电子发票开票信息明细表
        $invEleItemMdl = app::get('invoice')->model('order_electronic_items');
        $invEleItemMdl->update(array('serial_no'=>$data['fpqqlsh']),array('id' => $id, 'billing_type' => '1'));

        $retry = array(
            'obj_bn'        => $callback_params['order_bn'],
            'obj_type'      => 'einvoice_result',
            'channel'       => 'invoice',
            'channel_id'    => $callback_params['shop_id'],
            'method'        => 'invoice_create_result',
            'args'          => array(array('serial_no'=>$data['fpqqlsh'],'order_bn'=>$callback_params['order_bn'])),
        );
        app::get('erpapi')->model('api_fail')->saveRunning($retry);

        return $this->callback($response, $callback_params);
    }

    /**
     * 发票取消接口请求
     * 
     * @param array $sdf 请求参数
     */
    public function cancel($sdf){
        // 开票发起前 标记开电子发票同步状态为 开红票中
        app::get('invoice')->model('order')->update(array('sync'=>'4'),array('id' => $sdf['id']));

        $apiname = $this->get_create_apiname();

        $params = $this->get_create_params($sdf);

        if (!$params) {
            return array('rsp'=>'fail','err_msg'=>'税额、含税金额、不含税金额错误');
        }

        $callback = array(
          'class'  => get_class($this),
          'method' => 'cancel_callback',
          'params' => array(
            'id'       => $sdf['id'],
            'order_bn' => $sdf['order_bn'],
            'shop_id'  => $sdf['shop_id'],
          ),
        );

        $gateway = '';
        if ($sdf['is_encrypt']) {
            $params['order_bns']   = $sdf['order_bns'];
            $params['s_node_type'] = $sdf['s_node_type'];
            $params['s_node_id']   = $sdf['s_node_id'];

            $gateway = $sdf['s_node_type'];
        }

        $rs = $this->__caller->call($apiname,$params,$callback,'电子发票冲红',10,$sdf["order_bn"],true,$gateway);
        if ($rs['rsp'] == 'fail' && $sdf['id']) {
            $invOrderMdl = app::get('invoice')->model('order');
            $optMdl      = app::get('ome')->model('operation_log');
    
            $invOrderMdl->update(array('sync'=>'5','is_status'=>'1'),array('id'=>$sdf['id']));
            $optMdl->write_log('invoice_billing@invoice', $sdf['id'], sprintf('开票冲红失败：%s',$rs['msg']));
        }

        return $rs;
    }

    /**
     * 开蓝票callback函数
     * 
     * @param array $response
     * @param array $callback_params
     * @return array
     */
    public function cancel_callback($response, $callback_params)
    {
        //矩阵调用了 两个接口 alibaba.einvoice.createreq ERP开票请求接口 和 alibaba.einvoice.create.result.get ERP开票结果获取 都会callback回来
        //这里区分是哪个接口返回 step为1是alibaba.einvoice.createreq，step为2是alibaba.einvoice.create.result.get 并获取 $error_msg
        $rsp     = $response['rsp'];
        $err_msg = $response['err_msg'];
        $data    = $response['data'];
        $msg_id  = $response['msg_id'];
        $res     = $response['res'];

        $id = $callback_params['id'];

        $invOrderMdl = app::get('invoice')->model('order');
        $optMdl      = app::get('ome')->model('operation_log');

        // 开票失败,置为未开票
        if ($rsp != 'succ') {
            $invOrderMdl->update(array('sync'=>'5','is_status'=>'1'),array('id'=>$id));

            $optMdl->write_log('invoice_billing@invoice', $id, sprintf('开票冲红失败：%s',$err_msg));

            return $this->callback($response, $callback_params);
        }

        $data  =  @json_decode($data, true);

        // 更新电子发票开票信息明细表
        $invEleItemMdl = app::get('invoice')->model('order_electronic_items');
        $invEleItemMdl->update(array('serial_no'=>$data['fpqqlsh']),array('id' => $id, 'billing_type' => '2'));

        $invOrderMdl->update(array('sync'=>'4','is_status'=>'2','update_time'=>time()),array('id'=>$id));

        $retry = array(
            'obj_bn'        => $callback_params['order_bn'],
            'obj_type'      => 'einvoice_result',
            'channel'       => 'invoice',
            'channel_id'    => $callback_params['shop_id'],
            'method'        => 'invoice_create_result',
            'args'          => array(array('serial_no'=>$data['fpqqlsh'],'order_bn'=>$callback_params['order_bn'])),
        );
        app::get('erpapi')->model('api_fail')->saveRunning($retry);

        return $this->callback($response, $callback_params);
    }
    
     /**
      * 电子发票开票(蓝票或者红票)后获取发票开票结果查询
      * 同批量，目前先一条一条吧
      * 
      * @param array $sdf 请求参数
      */
     public function create_result($sdf){
        $apiname = $this->get_result_apiname($sdf);

        $params = $this->get_result_params($sdf);

        // 记录请求日志，便于重试
        $retry = array(
            'obj_bn'        => $sdf['order_bn'],
            'obj_type'      => 'einvoice_result',
            'channel'       => 'invoice',
            'channel_id'    => $this->__channelObj->channel['channel_id'],
            'method'        => 'invoice_create_result',
            'args'          => func_get_args(),
        );
        $apiFailId = app::get('erpapi')->model('api_fail')->saveRunning($retry);

        $result = $this->__caller->call($apiname,$params,null, '获取发票开票结果-'.$sdf['invoice_apply_bn'] ,20,$sdf["order_bn"]);

        $callback_params = array_merge($sdf,$params);
        $callback_params['api_fial_id'] = $apiFailId;

        return $this->create_result_callback($result, $callback_params);
    }
    
    /**
     * 创建_result_callback
     * @param mixed $result result
     * @param mixed $callback_params 参数
     * @return mixed 返回值
     */
    public function create_result_callback($result, $callback_params)
    {
        if ($result['rsp'] == 'succ' && $list = @json_decode($result['data'],true)) {
            $c = array();

            foreach ($list as $value) {
                $value['c_kprq'] = substr($value['c_kprq'],0,-3);

                if ($value['c_url'] || in_array($value['c_status'], ['22'])) {
                     $c[$value['c_fpqqlsh']] = $value;
                }

                // if (in_array($value['c_status'], ['21'])) {
                //     $result['err_msg'] = $value['c_orderno'].'//'.$value['c_resultmsg'];
                // }
            }

            if ($c) {
                if ($callback_params['api_fial_id']) app::get('erpapi')->model('api_fail')->delete(array('id' => $callback_params['api_fial_id']));

                $invEleItemMdl = app::get('invoice')->model('order_electronic_items');
                $invOrderMdl   = app::get('invoice')->model('order');
                $orderMdl      = app::get('ome')->model('orders');
                $apiFailMdl    = app::get('erpapi')->model('api_fail');
                $shopMdl       = app::get('ome')->model('shop');
                $orderInvoiceMdl = app::get('ome')->model('order_invoice');

                $items = $invEleItemMdl->getList('*',array('serial_no' => array_keys($c)),0,-1,'id asc');

                $invoiceIdArr = array();
                foreach ($items as $value) {
                    $invoiceIdArr[] = $value['id'];
                }

                $invoiceList = array(); $shop_id = array();
                if ($invoiceIdArr) {
                    foreach ($invOrderMdl->getList('*',array('id'=>$invoiceIdArr)) as $value) {
                        $invoiceList[$value['id']] = $value;

                        $shop_id[$value['shop_id']] = $value['shop_id'];
                    }
                }

                $shopList = array();
                if ($shop_id) {
                    foreach ($shopMdl->getList('shop_id,node_type',array('shop_id'=>$shop_id)) as $value) {
                        $shopList[$value['shop_id']] = $value;
                    }
                }

                foreach ($items as $value) {
                    // if ($value['url']) continue;

                    $kpInfo = $c[$value['serial_no']];
                    $shop = $shopList[$invoiceList[$value['id']]['shop_id']];

                    if (!$kpInfo) continue;
    
                    //发票本地存储开关控制
                    $file_id = 0;
                    if ('on' == app::get('ome')->getConf('ome.invoice.local.storage')) {
                        $file_id = kernel::single('invoice_order')->save_base_file(['url' => $kpInfo['c_url'], 'order_bn' => $invoiceList[$value['id']]['order_bn']]);
                    }
                    $invEleItemMdl->update(array(
                        'invoice_code'        => $kpInfo['c_fpdm'],
                        'invoice_no'          => $kpInfo['c_fphm'],
                        'url'                 => $kpInfo['c_url'] != 'no' ? $kpInfo['c_url'] : '',
                        'create_time'         => $kpInfo['c_kprq'],
                        'upload_tmall_status' => $shop['node_type'] == 'taobao' ? '2' : '1',
                        'file_id'             => $file_id,
                    ),array('item_id'=>$value['item_id']));

                    $invoiceOrderData = array(
                        'invoice_code' => $kpInfo['c_fpdm'],
                        'invoice_no'   => $kpInfo['c_fphm'],
                        'dateline'     => $kpInfo['c_kprq'],
                        'update_time'  => time(),
                    );

                    if ($value['billing_type'] == '1') {
                        $invoiceOrderData['sync'] = $kpInfo['c_status'] == '2' || $kpInfo['c_status'] == '21' ? '3' : '2';

                        if ($invoiceOrderData['sync'] == '3') {
                            $invoiceOrderData['is_status'] = '1';
                            $invoiceOrderData['is_make_invoice'] = '0';
                        } else {
                            $invoiceOrderData['is_status'] = '0';
                        }
                        $update = array('id'=>$value['id'],'sync' => array('0', '1', '2'));
                    }

                    if ($value['billing_type'] == '2') {
                        $invoiceOrderData['sync'] = $kpInfo['c_status'] == '2' || $kpInfo['c_status'] == '21' ? '6' : '5';

                        if ($invoiceOrderData['sync'] == '6') $invoiceOrderData['is_status'] = '2';
                        $update = array('id'=>$value['id'], 'sync' => array('3', '4', '5'));
                    }


                    $invOrderMdl->update($invoiceOrderData,$update);

                    // 更新订单
                    if ($invoiceList[$value['id']]['order_id']) {
                        $orderMdl->update(array(
                            'tax_no' => $kpInfo['c_fphm']
                        ),array('order_id'=>$invoiceList[$value['id']]['order_id']));
    
                        $orderInvoiceMdl->update(array(
                            'tax_no' => $kpInfo['c_fphm']
                        ),array('order_id'=>$invoiceList[$value['id']]['order_id']));
                    }
                    //冲红成功根据改票信息新建发票
                    if ($value['billing_type'] == '2' && $invoiceOrderData['sync'] == '6' && $invoiceOrderData['is_status'] == '2') {
                        if ($invoiceList[$value['id']]['changesdf'] && $invoiceList[$value['id']]['change_status'] == '1') {
                            $params = array_merge($invoiceList[$value['id']],json_decode($invoiceList[$value['id']]['changesdf'],1));
                            unset($params['is_status'],$invoiceList[$value['id']]['itemsdf']);
                            $type = 'change_ticket';
                            $params['is_edit'] = 'true';
                        }
                        if ($invoiceList[$value['id']]['action_type'] != 'create_order') {
                            //自动重新开票
                            $params = !empty($params) ? $params : $invoiceList[$value['id']];
                            unset($params['is_status'],$params['sync']);
                            $type = $invoiceList[$value['id']]['action_type'];
                        }
                        if ($params) {
                            $data = kernel::single('invoice_order')->formatAddData($params);
                            list($res,$msg) = kernel::single('invoice_process')->newCreate($data,$type);
                        }
                        //冲红成功修改合票发票
                        kernel::single('invoice_order')->cancelSuccessRes($value['id']);
                    }
                    // 发短信^自动上传
                    if ($invoiceOrderData['sync'] == '3' || $invoiceOrderData['sync'] == '6') {
                        app::get('ome')->model('operation_log')->write_log('invoice_billing@invoice', $value['id'], $invoiceOrderData['sync'] == '3'?'开票成功':'冲红成功');

                        // if (defined('APP_TOKEN') && defined('APP_SOURCE') && $callback_params['sendsms'] !== false){
                        //     kernel::single('taoexlib_sms_send_router','einvoice')->sendmsg($value['id']);
                        // }


                        if ('on' == app::get('ome')->getConf('ome.invoice.autoupload') && in_array($shopList[$invoiceList[$value['id']]['shop_id']]['node_type'], array('taobao','360buy','wesite','d1mwestore','pekon','luban','ecos.ecshopx'))) {

                            $apiFailMdl->saveTriggerRequest($kpInfo['c_fphm'], 'upload_invoice');
                            app::get('ome')->model('operation_log')->write_log('einvoice_upload@invoice', $value['id'], $invoiceOrderData['sync'] == '3'?'开票成功准备上传':'冲红成功准备上传');
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * 获取_result_apiname
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function get_result_apiname($sdf){
        return EINVOICE_CREATE_RESULT_GET;
     }

    /**
     * 获取_result_params
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function get_result_params($sdf)
     {
        $params = array();
        $params['jinshui_api_key'] = $this->__channelObj->channel['extend_data']['jinshui_api_key'];

        if ($sdf['serial_no']) {
            $params['fpqqlshs'] = json_encode((array)$sdf['serial_no']);
        } else {
            $params['tids'] = json_encode((array)$sdf['order_bn']);
        }

        return $params;
     }

    /**
     * 发票获取
     * @param $sdf
     * @return array
     */
    protected function _get_create_callback_params($sdf)
    {
        return array(
            'id' => $sdf['id'],
            'amount' => $sdf["jshjTotal"],  // 价税合计，就是开票金额
            'cost_tax' => $sdf["seTotal"],         // 价金
            'order_bn' => $sdf['order_bn'],
            'shop_id' => $sdf['shop_id'],
        );
    }

    public function upload($params)
    {
        $title           = "电子发票上传开票平台-" . $params['tid'];

        $rs = $this->__caller->call(SHOP_INVOICE_STATUS_UPDATE, $params, [], $title, 10, $params["tid"]);

        return $rs;
    }
}
