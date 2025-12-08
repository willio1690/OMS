<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2015-12-15
 * @describe 淘宝请求电子面单类
 */
class erpapi_logistics_matrix_taobao_request_electron extends erpapi_logistics_request_electron
{
    protected $directNum = 1;

    /**
     * bufferRequest
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function bufferRequest($sdf){
        return $this->directNum;
    }

    /**
     * directRequest
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function directRequest($sdf) {
        // 御城河-运单直连
        if ($sdf['order_bns']) {
            $hchsafe = array(
                'to_node_id' => $this->__configObj->get_to_node_id(),
                'tradeIds'   => $sdf['order_bns'],
            );

            kernel::single('base_hchsafe')->order_push_log($hchsafe);
        }


        $corp = app::get('ome')->model('dly_corp')->getList('prt_tmpl_id, protect, protect_rate, minprice,type, channel_id', array('corp_id'=>$sdf['delivery'][0]['logi_id']), 0, 1);

        // 模板地址
        if($corp['channel_id'] != $this->__channelObj->channel['channel_id']) {
            $prtTmpl = app::get('ome')->model('dly_corp_channel')->db_dump(
                array('channel_id'=>$this->__channelObj->channel['channel_id'], 'corp_id'=>$sdf['delivery'][0]['logi_id']), 'prt_tmpl_id');
            if($prtTmpl) {
                $corp[0]['prt_tmpl_id'] = $prtTmpl['prt_tmpl_id'];
            }
        }


        $templateObj = app::get("logisticsmanager")->model('express_template');
        $printTpl = $templateObj->getList('*', array('template_id'=>$corp[0]['prt_tmpl_id']), 0, 1);
        $template = $printTpl[0];
        $templateUrl = $this->_getCainiaoUrl($template);
        $this->title = '淘宝菜鸟_' . $this->__channelObj->channel['logistics_code'] . '获取电子面单';
        $this->timeOut = 6;
        $this->primaryBn = $sdf['primary_bn'];
        $arrDelivery = $sdf['delivery']; //发货单主表外，还需字段item_name、package_items、order_channels_type、order_bn
        $shop = $sdf['shop'];
        $userId = $shop['seller_id'];
        if(!$userId) {
            $userData = app::get('ome')->model('shop')->getList('addon', array('shop_id'=>$this->__channelObj->channel['shop_id']));
            $userId = $userData[0]['addon']['tb_user_id'];
        }
        $seller = array(
            'address' => array(
                'city' => $shop['city'],
                'detail' => $shop['address_detail'],
                'district' => $shop['area'],
                'province' => $shop['province'],
                #'town' => $shop['town'], //没有该字段的值
            ),
            'mobile' => $shop['mobile'],
            'name' => &$shop['shop_name'],
            'phone' => $shop['tel']
        );

        $index_type = ''; //加密类型
        $TradeOrderInfoDto = array();

        $is_encrypt = false; $jst = array ('order_bns' => array ());
        foreach($arrDelivery as $delivery) {
            $oaid = '';
            $oaidTid = '';
            if($delivery['shop_type'] == 'taobao') {
                foreach ($delivery as $dk => $dv) {
                    if(is_string($dv) && $index = strpos($dv , '>>')) {
                        $delivery[$dk] = substr($dv , 0, $index);
                        $oaid = substr($dv, $index+2, -5);
                    }
                }
                if($oaid && $delivery['order_bns']) {
                    $oaidArrOrder = app::get('ome')->model('orders')->getList('order_bn,order_id,order_source,order_type,relate_order_bn', ['order_bn'=>$delivery['order_bns'],'ship_name|has'=>$oaid]);
                    
                    //补发订单--使用平台订单号获取物流运单号
                    if($oaidArrOrder[0]['order_type'] == 'bufa' && $oaidArrOrder[0]['relate_order_bn']){
                        $oaidTid = $oaidArrOrder ? $oaidArrOrder[0]['relate_order_bn'] : current($delivery['order_bns']);
                        
                        //平台订单号
                        $delivery['order_bns'] = $oaidArrOrder[0]['relate_order_bn'];
                        $delivery['order_bn'] = array($oaidArrOrder[0]['relate_order_bn']);
                    }else{
                        $oaidTid = $oaidArrOrder ? $oaidArrOrder[0]['order_bn'] : current($delivery['order_bns']);
                    }
                    
                    if($oaidArrOrder) {
                        $order = $oaidArrOrder[0];
                        if($order['order_source'] == 'maochao') {
                            $shop['shop_name'] = '天猫国际';
                            $orderExtend = app::get('ome')->model('order_extend')->db_dump(['order_id'=>$order['order_id']], 'extend_field');
                            $extend_field = @json_decode($orderExtend['extend_field'], 1);
                            if($extend_field['oaidSourceCode']) {
                                $oaidTid = $extend_field['oaidSourceCode'];
                            }
                        }
                    }
                }
            }
            if( in_array($delivery['shop_type'], ['alibaba4ascp','alibaba']) ) {
                foreach ($delivery as $dk => $dv) {
                    if(is_string($dv) && $index = strpos($dv , '>>')) {
                        $delivery[$dk] = substr($dv , 0, $index);
                        $caidTid = current($delivery['order_bns']);
                    }
                }
                if($caidTid) {
                    $caidOrder = kernel::database()->selectrow('select order_id from sdb_ome_orders where order_bn="'.$caidTid.'" and shop_id="'.$delivery['shop_id'].'"');
                    if($caidOrder) {
                        $original = app::get('ome')->model('order_receiver')->db_dump(['order_id'=>$caidOrder['order_id']], 'encrypt_source_data');
                        if($original) {
                            $encrypt_source_data = json_decode($original['encrypt_source_data'], 1);
                            
                            //订单新增【OAID】字段替代【CAID】
                            if($encrypt_source_data['origin_oaid_field']){
                                $oaid = $encrypt_source_data['origin_oaid_field']; //oaid
                                $index_type = 'oaid';
                            }else{
                                $caid = $encrypt_source_data['origin_caid_field']; //caid
                                $index_type = 'caid';
                            }
                        }
                    }
                }
            }
            // 是否加密
            if (!$is_encrypt) {
                $is_encrypt = kernel::single('ome_security_router',$delivery['shop_type'])->is_encrypt($delivery,'delivery');
            }
            if ($is_encrypt && $delivery['shop_type'] == "haoshiqi") {
                $oaidArrOrder = app::get('ome')->model('orders')->getList('order_id', ['order_bn' => $delivery['order_bns']]);
                $original     = app::get('ome')->model('order_receiver')->db_dump(['order_id' => current($oaidArrOrder)['order_id']], 'encrypt_source_data');
                if ($original) {
                    $encrypt_source_data = json_decode($original['encrypt_source_data'], 1);
                    if ($encrypt_source_data['is_consignee_encrypt'] && $encrypt_source_data['taobao_oaid']) {
                        foreach ($delivery as $dk => $dv) {
                            if($index = strpos($dv , '>>')) {
                                $delivery[$dk] = substr($dv , 0, $index);
                            }
                        }
                        $oaidTid = $encrypt_source_data['outer_order_id'];
                        $oaid = $encrypt_source_data['taobao_oaid'];
                        $is_encrypt              = false;
                    }
                }
            }
            $jst['order_bns'] = array_merge($jst['order_bns'], $delivery['order_bns']);

            $deliveryBnKey[$delivery['delivery_bn']] = $delivery;
            $arrItem = array();
            foreach($delivery['package_items'] as $pVal) {
                $arrItem[] = array(
                    'count' => $pVal['count'],
                    'name' => $pVal['item_name']
                );
            }
            // 服务订单
            $logisticsServices = $this->_getLogisticsServices($corp[0], $delivery);

            if($logisticsServices['brand_code']){
                $brand_code = $logisticsServices['brand_code']['value'];
                unset($logisticsServices['brand_code']);
            }

            if($logisticsServices['SF-PAY-METHOD']){
                $sf_pay_method = $logisticsServices['SF-PAY-METHOD']['value'];
                unset($logisticsServices['SF-PAY-METHOD']);
            }

            if($logisticsServices['customer_code']){
                $customer_code = $logisticsServices['customer_code']['value'];
                unset($logisticsServices['customer_code']);
            }

            if($logisticsServices['brand_code_customer_code']){
                list($brand_code, $customer_code) = explode('-', $logisticsServices['brand_code_customer_code']['value']);
                unset($logisticsServices['brand_code_customer_code']);
            }
            
            //云集分销订单处理
            if ($delivery['shop_type'] == 'yunji4fx') {
                $oaidArrOrder = app::get('ome')->model('orders')->getList('order_id', ['order_bn'=>$delivery['order_bns']]);
                $original = app::get('ome')->model('order_receiver')->db_dump(['order_id'=>current($oaidArrOrder)['order_id']], 'encrypt_source_data');
                if($original) {
                    $encrypt_source_data = json_decode($original['encrypt_source_data'], 1);
                    if ($encrypt_source_data['external_channel_code'] == 'TM') {
                        $oaidTid = $encrypt_source_data['channel_order_no'];
                        $oaid = $encrypt_source_data['oaid'];
                        $delivery['ship_addr'] = $encrypt_source_data['receiver_address_index'];
                        $delivery['ship_mobile'] = $encrypt_source_data['receiver_mobile_index'];
                        $delivery['ship_name'] = $encrypt_source_data['buyer_name_index'];
                        $delivery['ship_tel'] = $encrypt_source_data['receiver_phone_index'];
                        $is_encrypt = false;
                    }
                }
            }
            $tmp = array(
                'logistics_services' => $logisticsServices ? $logisticsServices : new stdClass(),
                'object_id' => $delivery['delivery_bn'],
                'order_info' => array(
                    'order_channels_type' => $delivery['order_channels_type'],
                    'trade_order_list' => empty($delivery['order_bn']) ? [$delivery['delivery_bn']] : array_unique($delivery['order_bn']),
                ),
                'package_info' => array(
                    'id'                => $delivery['delivery_bn'],
                    'goods_description' =>  $delivery['goods_description'],
                    'items' => $arrItem,
                    'total_packages_count' => intval($delivery['logi_number']),
                    'weight'               => (int)$delivery['net_weight'],
                ),
                'recipient' => array(
                    'address' => array(
                        'city' => $delivery['ship_city'],
                        'detail' => $delivery['ship_addr'],
                        'district' => $delivery['ship_district'],
                        'province' => $delivery['ship_province'],
                        #'town' => $delivery['ship_town'], //没有该字段的值
                    ),
                    'mobile' => $delivery['ship_mobile'],
                    'name' => $delivery['ship_name'],
                    'phone' => $delivery['ship_tel'],
                    'oaid'  => $oaid ? : '',
                    'caid'  => $caid ? : '',
                    'tid'  => $oaid ? $oaidTid : ($caid ? $caidTid : ''),
                ),
                'template_url' => $templateUrl,
                'user_id' => $userId,

                // 解密字段
                'order_bns'        => implode(',', $delivery['order_bns']),
            );
            if($this->__channelObj->channel['logistics_code'] == 'SF' && !empty($delivery['order_bn'])) {
                $tmp['order_info']['trade_order_list'] = [$delivery['delivery_bn']];
            }
            if ($brand_code == 'FW') {
                unset($tmp['package_info']['total_packages_count']);
            }
            if ($brand_code == 'FOP') {
                $tmp['package_info']['length'] = 30;
                $tmp['package_info']['width'] = 30;
                $tmp['package_info']['height'] = 30;
            }
            $TradeOrderInfoDto[] = $tmp;
         
            /* if($value['order_bool_type'] & ome_order_bool_type::__3PL_CODE) {
                $cnService = '3p';
            } else {
                $cnService = '';
            } */
        }
        $params = array(
            //'product_code' => 'STANDARD_EXPRESS',
            'sender' => json_encode($seller),
            'trade_order_info_dtos' => json_encode($TradeOrderInfoDto),
            'three_pl_timing'       =>  $cnService == '3p' ? 'true' : 'false',
            'index_type' => $index_type, //加密类型oaid、caid
        );
        if ($this->__channelObj->channel['exp_type'] && $this->__channelObj->channel['logistics_code'] == 'SF'){
            $params['product_code'] = $this->__channelObj->channel['exp_type'];
        }
        if ($brand_code)    $params['brand_code']       = $brand_code;
        if ($customer_code) $params['customer_code']    = $customer_code;
        if ($sf_pay_method) $params['extra_info']['payMethod']     = $sf_pay_method;

        // 京东快递/京东快运
        if (in_array($this->__channelObj->channel['logistics_code'],[ 'LE04284890', 'LE38288910'])) {
            $params['product_code'] = $this->__channelObj->channel['exp_type'];
            $params['extra_info']['whOrderCode']    = $delivery['delivery_bn'];
        }

        // 菜鸟老接口不传product_code
        if ($brand_code == 'default' && $this->__channelObj->channel['logistics_code'] == 'SF'){
            $serviceCode = json_decode($this->__channelObj->channel['service_code'], 1);
            if ($serviceCode['PAYMENT-TYPE']){
                $params['product_code'] = '';
            }
        }
        // 加密请求虎符
        $gateway = '';
        if ($is_encrypt) {
            $params['s_node_id']    = $delivery['shop']['node_id'];
            $params['s_node_type']  = $delivery['shop_type'];
            $params['order_bns']    = implode(',', $delivery['order_bns']);

            $gateway = $delivery['shop_type'];
        }
        if($params['extra_info']) {
            $params['extra_info'] = json_encode($params['extra_info']);
        }
        //淘宝推送拆合单结果
        $this->pushOrderAssemble($sdf['delivery']);
        
        // 超时请求3次
        $requestCount = 0;
        do {
            $result = $this->requestCall(STORE_CAINIAO_WAYBILL_I_GET, $params, array(), $jst, $gateway);

            // 判断是否请求超时
            if ($result['rsp'] != 'fail' || ($result['res_ltype'] != 1 && $result['res_ltype'] != 2) ) {
                break;
            }
            $requestCount++;
        } while ($requestCount<3);
        $result['shop'] = $shop;
        return $this->backToResult($result, $deliveryBnKey);
    }

    private function _getCainiaoUrl($template) {
        if(in_array($template['template_type'], array('cainiao_user', 'cainiao_standard'))) {
            $url = explode(':', $template['template_data'], 2);
        } else {
            $filter = array(
                'template_type' => 'cainiao_standard'
            );
            $logisticsTaobao = kernel::single('logisticsmanager_waybill_taobao');
            $logistics = $logisticsTaobao->logistics($this->__channelObj->channel['logistics_code']);
            $filter['template_name'] = $logistics['name'] . '菜鸟标准云模板';
            $templateObj = app::get("logisticsmanager")->model('express_template');
            $printTpl = $templateObj->getList('*', $filter, 0, 1);
            if($printTpl) {
                $url = explode(':', $printTpl[0]['template_data'], 2);
            } else {
                $url[1] = 'http://cloudprint.cainiao.com/cloudprint/template/getStandardTemplate.json?template_id=1001&version=38';
            }
        }
        return $url[1];
    }

    private function _getLogisticsServices($corp, $delivery){
        $logisticsServices = array();
        if($this->__channelObj->channel['service_code']) {
            $serviceCode = json_decode($this->__channelObj->channel['service_code'], 1);
            $serviceCodeValue = kernel::single('logisticsmanager_waybill_taobao')->getServiceCodeValue($this->__channelObj->channel['logistics_code'], $serviceCode);
            if($serviceCodeValue['SVC-COD']) {
                unset($serviceCodeValue['SVC-COD']);
                if ($delivery['is_cod'] == 'true') {
                    $logisticsServices['SVC-COD']['value'] = $delivery['receivable'];
                }
            }
            if($serviceCodeValue['COD']) {
                unset($serviceCodeValue['COD']);
                if ($delivery['is_cod'] == 'true') {
                    $logisticsServices['COD']['value'] = $delivery['receivable'];
                }
            }
            if($serviceCodeValue['SVC-BESTQJT-COD']) {
                if ($delivery['is_cod'] == 'true') {
                    $logisticsServices['SVC-BESTQJT-COD'] = $serviceCodeValue['SVC-BESTQJT-COD'];
                    $logisticsServices['SVC-BESTQJT-COD']['value'] = $delivery['receivable'];
                }
                unset($serviceCodeValue['SVC-BESTQJT-COD']);
            }
            if($serviceCodeValue['SVC-INSURE']) {
                unset($serviceCodeValue['SVC-INSURE']);
                if ($corp['protect'] == 'true') {
                    $logisticsServices['SVC-INSURE'] = in_array($this->__channelObj->channel['logistics_code'], ['EMS','POSTB']) ? new stdClass : ['value'=>max($delivery['total_amount'] * $corp['protect_rate'], $corp['minprice'])];
                }
            }
            if($serviceCodeValue['INSURE']) {
                unset($serviceCodeValue['INSURE']);
                if ($corp['protect'] == 'true') {
                    $logisticsServices['INSURE'] = in_array($this->__channelObj->channel['logistics_code'], ['EMS','POSTB']) ? new stdClass : ['value'=>max($delivery['total_amount'] * $corp['protect_rate'], $corp['minprice'])];
                }
            }

            if($serviceCodeValue['SVC-BESTQJT-INSURE']) {
                unset($serviceCodeValue['SVC-BESTQJT-INSURE']);
                if ($corp['protect'] == 'true') {
                    $logisticsServices['SVC-BESTQJT-INSURE']['value'] = max($delivery['total_amount'] * $corp['protect_rate'], $corp['minprice']);
                }
            }

            if($serviceCodeValue['SVC-RECEIVER-PAY']) {
                unset($serviceCodeValue['SVC-RECEIVER-PAY']);
                if ($delivery['is_cod'] == 'true') {
                    $logisticsServices['SVC-RECEIVER-PAY']['value'] = $delivery['receivable'];
                }
            }
            $logisticsServices = array_merge((array)$logisticsServices, (array)$serviceCodeValue);
        }
        return $logisticsServices;
    }
    private function backToResult($ret, $deliveryBnKey){
        $data = empty($ret['data']) ? array() : json_decode($ret['data'], true);
        
        $waybill = $data['waybill_cloud_print_response'];
        if(empty($waybill)) {
            if($ret['msg']) {
                $msg = @json_decode($ret['msg'], true);

                if (is_string($msg) && $msg) return $msg;

                if ($msg['error_response']['sub_msg']) $ret['msg'] = $msg['error_response']['sub_msg'];
            }

            return $ret['msg'] ? $ret['msg'] : false;
        }
        $result = array();
        foreach ($waybill as $val) {
            $deliveryBn = trim($val['object_id']);
            $delivery = $deliveryBnKey[$deliveryBn];
            $printData = json_decode($val['print_data'], true);
            $position = $printData['data']['routingInfo']['sortation']['name'] . ' ' .$printData['data']['routingInfo']['routeCode'];
            $packageWdjc = $printData['data']['routingInfo']['consolidation']['name'];
            $packageWd = $printData['data']['routingInfo']['consolidation']['code'];
            $result[] = array(
                'succ' => $val['waybill_code'] ? true : false,
                'msg' => '',
                'delivery_id' => $delivery['delivery_id'],
                'delivery_bn' => $delivery['delivery_bn'],
                'logi_no' => $val['waybill_code'],
                'mailno_barcode' => '',
                'qrcode' => '',
                'position' => trim($position),
                'position_no' => '',
                'package_wdjc' => (string) $packageWdjc,
                'package_wd' => (string) $packageWd,
                'print_config' => json_encode(['shop_seller'=>$ret['shop']]),
                'json_packet' => str_replace(array('&#34;','“','&quot;','&quot',),array('”','”','”','”'), $val['print_data']),
            );
        }
        $this->directDataProcess($result);
        return $result;
    }

    /**
     * recycleWaybill
     * @param mixed $waybillNumber waybillNumber
     * @param mixed $delivery_bn delivery_bn
     * @return mixed 返回值
     */
    public function recycleWaybill($waybillNumber,$delivery_bn = '') {
        
        $this->title = '淘宝菜鸟_' . $this->__channelObj->channel['logistics_code'] . '取消电子面单';
        $this->primaryBn = $waybillNumber;
        $params = array(
            'waybill_code' => $waybillNumber
        );
        $callback = array(
            'class' => get_class($this),
            'method' => 'callback'
        );
        
        if ($delivery_bn) {
            $this->pushOrderAssemble(array('delivery_bn'=>$delivery_bn),'CANCEL_MERGE');
        }
        
        $this->requestCall(STORE_CAINIAO_WAYBILL_CANCEL, $params, $callback);
    }

    /**
     * 获取淘宝订购地址
     * 
     * @return void
     * @author 
     * */
    public function getWaybillISearch($sdf = array())
    {
        $params = array(
            'cp_code'    => $this->__channelObj->channel['logistics_code'],
        );

        $title = '淘宝订购地址获取';

        $result = $this->__caller->call(STORE_CN_WAYBILL_II_SEARCH,$params,array(),$title, 6, $params['cp_code']);

        if ($result['rsp'] == 'succ' && $result['data']) {
            $this->_getWISCallback($result['data']);
        }

        return array('rsp'=>$result['rsp'],'msg'=>$result['rsp']=='succ'?'获取成功':'获取失败');
    }

    private function _getWISCallback($data)
    {
        $data = json_decode($data,true);

        if (!$data) return ;

        $extendObj = app::get('logisticsmanager')->model('channel_extend');
        $shopModel = app::get('ome')->model('shop');

        $channel_id = $this->__channelObj->channel['channel_id'];
        $shop = $shopModel->dump(array('shop_id'=>$this->__channelObj->channel['shop_id']),'addon');

        // 取有面单号的
        foreach ($data['waybill_apply_subscription_info'][0]['branch_account_cols']['waybill_branch_account'] as $info) {
                $process_params = array(
                    'cancel_quantity'    => $info['cancel_quantity'],
                    'allocated_quantity' => $info['quantity'],
                    'province'           => $info['shipp_address_cols']['address_dto'][0]['province'],
                    'city'               => $info['shipp_address_cols']['address_dto'][0]['city'],
                    'area'               => $info['shipp_address_cols']['address_dto'][0]['district'],
                    'address_detail'     => $info['shipp_address_cols']['address_dto'][0]['detail'],
                    'channel_id'         => $channel_id,
                    'print_quantity'     => $info['print_quantity'],
                    'seller_id'          => $shop['addon']['tb_user_id'],
                );
                // $process_params = array(
                //     'cancel_quantity'    => $info['cancel_quantity'],
                //     'allocated_quantity' => $info['quantity'],
                //     'province'           => $info['shipp_address_cols']['waybill_address'][0]['province'],
                //     'city'               => $info['shipp_address_cols']['waybill_address'][0]['city'],
                //     'area'               => $info['shipp_address_cols']['waybill_address'][0]['area'],
                //     'address_detail'     => $info['shipp_address_cols']['waybill_address'][0]['address_detail'],
                //     'waybill_address_id' => $info['shipp_address_cols']['waybill_address'][0]['waybill_address_id'],
                //     'channel_id'         => $channel_id,
                //     'print_quantity'     => $info['print_quantity'],
                //     'seller_id'          => $info['seller_id'],   
                // );
                
                if ($info['quantity'] > 0) break;
        }

        if (!$process_params) return ;
        
        $extend = $extendObj->dump(array('channel_id'=>$channel_id),'id');
        if ($extend) $process_params['id'] = $extend['id'];

        $extendObj->save($process_params);
    }
    
    /**
     * 推送拆合单结果回传接口
     * @Author: xueding
     * @Vsersion: 2022/7/27 下午5:42
     * @param $sdf
     * @param string $type MERGE、CANCEL_MERGE
     * @return mixed
     */
    public function pushOrderAssemble($sdf, $type = 'MERGE')
    {
        $result = array();
        if ($type == 'MERGE') {
            foreach ($sdf as $key => $value) {
                $orderList = array();
                foreach ($value['delivery_order'] as $k => $orderItem) {
                    $orderList[] = array(
                        'order_type'             => 0,
                        'item_type'              => 0,
                        'order_id'               => $orderItem['order_id'],
                        'erp_order_id'           => $orderItem['order_bn'],
                        'taobao_parent_order_id' => $orderItem['order_id'],
                    );
                }
                $p = array(
                    'assemble_orders' => array(
                        'group_id'   => $value['delivery_bn'],
                        'order_list' => $orderList,
                    ),
                    'type'            => $type,
                );
                
                //https://open.taobao.com/api.htm?spm=a219a.7386797.0.0.4e3f669aljQIQh&source=search&docId=62880&docType=2
                $params = array(
                    'api'  => 'taobao.fulfillment.order.assemble',
                    'data' => json_encode($p),
                );
                
                $rs = $this->__caller->call(TAOBAO_COMMON_TOP_SEND, $params, array(), '拆合单结果回传接口', 10,
                    $value['delivery_bn']);
                
                if ($rs['rsp'] == 'succ') {
                    $rs['data'] = json_decode($rs['data'], true);
                    $result     = array_merge_recursive($result, (array)$rs['data']);
                }
            }
        } else {
            $p = array(
                'assemble_orders' => array(
                    'group_id' => $sdf['delivery_bn'],
                ),
                'type'            => $type,
            );
            
            //https://open.taobao.com/api.htm?spm=a219a.7386797.0.0.4e3f669aljQIQh&source=search&docId=62880&docType=2
            $params = array(
                'api'  => 'taobao.fulfillment.order.assemble',
                'data' => json_encode($p),
            );
            
            $rs = $this->__caller->call(TAOBAO_COMMON_TOP_SEND, $params, array(), '拆合单结果回传接口', 10, $sdf['delivery_bn']);
            
            if ($rs['rsp'] == 'succ') {
                $rs['data'] = json_decode($rs['data'], true);
                $result     = array_merge_recursive($result, (array)$rs['data']);
            }
        }
        return $result;
    }
}