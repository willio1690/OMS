<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_logistics_matrix_kuaishou_request_electron extends erpapi_logistics_request_electron
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
        $this->title     = '快手-' . $this->__channelObj->channel['logistics_code'] . '获取电子面单';
        $this->timeOut   = 20;
        $this->primaryBn = $sdf['primary_bn'];
        $corp = app::get('ome')->model('dly_corp')->getList('prt_tmpl_id, protect, protect_rate, minprice', array('corp_id'=>$sdf['delivery'][0]['logi_id']), 0, 1);
        $templateObj = app::get("logisticsmanager")->model('express_template');
        $printTpl = $templateObj->getList('*', array('template_id'=>$corp[0]['prt_tmpl_id']), 0, 1);
        $template = $printTpl[0];
        $templateData = json_decode($template['template_data'], 1);
        $templateUrl = $templateData['template_url'];
        // 发货地址
        $senderContract = array(
            'name'   => $sdf['shop']['default_sender'],
            'telephone'  => $sdf['shop']['tel'],
            'mobile'  => $sdf['shop']['mobile'],
        );
        $senderAddress = [
            'provinceName' => $sdf['shop']['province'],
            'cityName'     => $sdf['shop']['city'],
            'detailAddress'   => $sdf['shop']['address_detail'],
            'districtName' => $sdf['shop']['area'],
            'streetName' => $sdf['shop']['street'],
        ];
        $serviceCode = json_decode($this->__channelObj->channel['service_code'], 1);

        $arrDelivery = $sdf['delivery'];
        $deliveryBnKey = array();
        $getEbillOrderRequest = array();
        $is_encrypt = false; $jst = array ('order_bns' => array ());
        foreach($arrDelivery as $delivery) {
            //分销一件代发订单
            if($delivery['cos_id'] > 0){
                //替换使用平台订单号
                $getPlatformOrder = $this->_formatPlatformOrderBn($delivery);
                
                //order_bns
                if($getPlatformOrder['order_bns']){
                    $delivery['order_bns'] = $getPlatformOrder['order_bns'];
                }
                
                //order_bn
                if($getPlatformOrder['order_bn']){
                    $delivery['order_bn'] = $getPlatformOrder['order_bn'];
                }
            }
            
            // 是否加密
            if (!$is_encrypt) {
                $is_encrypt = kernel::single('ome_security_router',$delivery['shop_type'])->is_encrypt($delivery,'delivery');
                if($is_encrypt && $delivery['shop_type'] == "kuaishou") {
                    $encryptData = [
                        'shop_id' => $delivery['shop']['shop_id'],
                        'order_bn' => reset($delivery['order_bns']),
                        'ship_name' => $delivery['ship_name'],
                        'ship_tel' => $delivery['ship_tel'],
                        'ship_mobile' => $delivery['ship_mobile'],
                        'ship_addr' => $delivery['ship_addr'],
                    ];
                    $originalEncrypt = kernel::single('ome_security_kuaishou')->get_encrypt_body($encryptData, 'delivery');
                    $originalEncrypt = json_decode($originalEncrypt['data'], 1);
                    $originalEncrypt = current($originalEncrypt);
                    foreach ($originalEncrypt as $dk => $dv) {
                        $delivery[$dk] = $dv;
                        $is_encrypt = false;
                    }
                }elseif ($is_encrypt && $delivery['shop_type'] == "haoshiqi") {
                    $oaidArrOrder = app::get('ome')->model('orders')->getList('order_id', ['order_bn' => $delivery['order_bns']]);
                    $original     = app::get('ome')->model('order_receiver')->db_dump(['order_id' => current($oaidArrOrder)['order_id']], 'encrypt_source_data');
                    if ($original) {
                        $encrypt_source_data = json_decode($original['encrypt_source_data'], 1);
                        if ($encrypt_source_data['is_consignee_encrypt'] && $encrypt_source_data['third_info']) {
                            $delivery['delivery_bn'] = $encrypt_source_data['outer_order_id'];
                            $delivery['ship_addr']   = $encrypt_source_data['receiver_address_index'];
                            $delivery['ship_mobile'] = $encrypt_source_data['receiver_mobile_index'];
                            $delivery['ship_name']   = $encrypt_source_data['receiver_name_index'];
                            $delivery['ship_tel']    = $encrypt_source_data['receiver_mobile_index'];
                            $is_encrypt              = false;
                        }
                    }
                }
            }
            if($delivery['shop_type'] == "kuaishou") {
                $userData = app::get('ome')->model('shop')->dump(array('shop_id'=>$delivery['shop']['shop_id']),'addon,name');
                $userId = $userData['addon']['user_id'];
            }
            if(!$userId) {
                $shopId = $this->__channelObj->channel['shop_id'];
                $userData = app::get('ome')->model('shop')->dump(array('shop_id'=>$shopId), 'addon,name');
                $userId = $userData['addon']['user_id'];
            }
            $jst['order_bns'] = array_merge($jst['order_bns'], $delivery['order_bns']);
            $bnKey = $delivery['delivery_bn'];
            $main_order_no = '';
            kernel::single('wms_event_trigger_logistics_data_electron_common')->checkChildRqOrdNo($delivery['delivery_bn'], $main_order_no, $bnKey);

            $arrItem = array();
            foreach($delivery['package_items'] as $pVal) {
                $arrItem[] = array(
                    'itemQuantity' => $pVal['count'],
                    'itemTitle'  => $this->charFilter($pVal['item_name'])
                );
            }
            $order_bn = current($delivery['order_bn']);
            $logisticsServices = $this->_getLogisticsServices($corp[0], $delivery);
            $tmp = array(
                'merchantCode' => $userId,
                'packageCode' => $bnKey,
                'itemList' => $arrItem,
                'receiverContract' => [
                    'mobile' => $delivery['ship_mobile'],
                    'name'   => $delivery['ship_name'],
                    'telephone'  => '',//$delivery['ship_tel']
                ],
                'totalPackageQuantity' => intval($delivery['logi_number']),
                'netSiteCode' => $serviceCode['NETWORK-CODING']['value'],
                'netSiteName' => $serviceCode['NETWORK-NAME']['value'],
                'payMethod' => 1,
                'senderContract' => $senderContract,
                'expressCompanyCode' => $this->__channelObj->channel['logistics_code'],
                'orderChannel' => $delivery['order_channels_type'],
                'merchantName' => $userData['name'],
                'tradeOrderCode' => $order_bn ? : $delivery['delivery_bn'],
                'senderAddress' => $senderAddress,
                'templateUrl' => $templateUrl,
                'receiverAddress' => [
                    'cityName'     => $delivery['ship_city'],
                    'detailAddress'   => $delivery['ship_addr'],
                    'districtName' => $delivery['ship_district']?$delivery['ship_district']:'区',
                    'provinceName' => $delivery['ship_province'],
                ],
                'requestId'=> $this->uniqid(),
            );
            
            //四级地区(镇)
            if($delivery['ship_town']){
                $tmp['receiverAddress']['town_name'] = $delivery['ship_town'];
            }
            
            $deliveryBnKey[$tmp['requestId']] = $delivery;
            if($serviceCode['expressProductCode']['value']) {
                $tmp['expressProductCode'] = $serviceCode['expressProductCode']['value'];
            }
            if($serviceCode['settleAccount']['value']) {
                $tmp['settleAccount'] = $serviceCode['settleAccount']['value'];
            }
            if($serviceCode['isvClientCode']['value']) {
                $extData['isvClientCode'] = $serviceCode['isvClientCode']['value'];
            }
            if($this->__channelObj->channel['logistics_code'] == 'POSTB') {
                $extData['oneBillFeeType'] = 1;
            }
            if($extData) {
                $tmp['extData'] = json_encode($extData);
            }
            $getEbillOrderRequest[] = $tmp;
        }

        $params = ['param'=>json_encode([
                                'getEbillOrderRequest' => $getEbillOrderRequest
                            ])];
        // 加密请求虎符
        $gateway = '';
        if ($is_encrypt) {
            $params['s_node_id']    = $delivery['shop']['node_id'];
            $params['s_node_type']  = $delivery['shop_type'];

            $gateway = $delivery['shop_type'];
        }

        $result = $this->requestCall(STORE_KS_WAYBILL_GET, $params,  array(), $jst, $gateway);
        $returnResult = $this->backToResult($result, $deliveryBnKey);

        return $returnResult;
    }

    private function _getLogisticsServices($corp, $delivery){
        return array();//暂时不支持
        $logisticsServices = array();
        if($this->__channelObj->channel['service_code']) {
            $serviceCode = json_decode($this->__channelObj->channel['service_code'], 1);

            foreach ($serviceCode as $k => $v) {
                if(in_array($k, ['COD', 'SVC-COD'])) {
                    if ($delivery['is_cod'] == 'true' && $v['value'] == '1') {
                        $logisticsServices[] = [
                            'service_code' => $k,
                            'service_value' => $delivery['receivable']
                        ];
                    }
                    continue;
                }
                if(in_array($k, ['INSURE', 'SVC-INSURE'])) {
                    if ($corp['protect'] == 'true' && $v['value'] == '1') {
                        $protectValue = max($delivery['total_amount'] * $corp['protect_rate'], $corp['minprice']);
                        $logisticsServices[] = [
                            'service_code' => $k,
                            'service_value' => $protectValue
                        ];
                    }
                    continue;
                }
                //月结号
                if($k == 'monthly_account') {
                    $logisticsServices['monthly_account'] = $v['value'];
                    continue;
                }

                //音尊达
                if($k == 'SVC-WBHOMEDELIVERY' && $v['value'] == '1'){
                    $logisticsServices[] = [
                        'service_code' => $k,
                        'service_value' => json_encode(array('value'=>''))
                    ];continue;
                }

                if($v['value']) {
                    $logisticsServices[] = [
                        'service_code' => $k,
                        'service_value' => $v['value']
                    ];
                }
            }
        }
        return $logisticsServices;
    }

    private function backToResult($ret, $deliveryBnKey){
        $waybill = empty($ret['data']) ? array() : json_decode($ret['data'], true);
        if(empty($waybill) || $ret['rsp'] == 'fail') {
            return $ret['msg'] ? $ret['msg'] : false;
        }
        $result = array();
        foreach ($waybill as $v) {
            $deliveryBn = trim($v['requestId']);
            $delivery = $deliveryBnKey[$deliveryBn];
            foreach ($v['data'] as $val) {
                $result[] = array(
                    'succ' => $val['waybillCode'] ? true : false,
                    'msg' => '',
                    'delivery_id' => $delivery['delivery_id'],
                    'delivery_bn' => $delivery['delivery_bn'],
                    'logi_no' => $val['waybillCode'],
                    'mailno_barcode' => '',
                    'qrcode' => '',
                    'position' => '',
                    'position_no' => '',
                    'package_wdjc' => '',
                    'package_wd' => '',
                    'print_config' => '',
                    'json_packet' => json_encode($val),
                );
            }
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
        app::get('logisticsmanager')->model('waybill')->update(array('status'=>2,'create_time'=>time()),array('waybill_number'=>$waybillNumber));
        $this->title = '快手_' . $this->__channelObj->channel['logistics_code'] . '取消电子面单';
        $this->primaryBn = $waybillNumber;
        $params = array(
            'company_code' => $this->__channelObj->channel['logistics_code'],
            'waybill_code' => $waybillNumber
        );
        $callback = array(
            'class' => get_class($this),
            'method' => 'callback'
        );
        $this->requestCall(STORE_KS_WAYBILL_CANCEL, $params, $callback);
    }

    public function getWaybillISearch($sdf = array())
    {
        $params = array(
            'company_code' => $this->__channelObj->channel['logistics_code'],
        );

        $title = '快手订购地址获取';

        $result = $this->__caller->call(STORE_KS_ADDRESS,$params,array(),$title, 6, $this->__channelObj->channel['logistics_code']);
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

        // 取有面单号的
        $process_params = array();
        foreach ($data as $info) {
            if($info['availableQuantity'] < $process_params['allocated_quantity']) {
                continue;
            }
            $process_params = array(
                'cancel_quantity'    =>  0,
                'allocated_quantity' => $info['availableQuantity'],
                'province'           => $info['senderAddress'][0]['provinceName'],
                'city'               => $info['senderAddress'][0]['cityName'],
                'area'               => $info['senderAddress'][0]['districtName'],
                'street'             => $info['senderAddress'][0]['streetName'],
                'address_detail'     => $info['senderAddress'][0]['detailAddress'],
                'channel_id'         => $this->__channelObj->channel['channel_id'],
                'print_quantity'     => 0,
            );
            if($process_params['print_quantity'] < 0) {
                $process_params['print_quantity'] = 0;
            }
        }

        if (!$process_params) return ;

        $extend = $extendObj->dump(array('channel_id'=>$this->__channelObj->channel['channel_id']),'id');
        if ($extend) $process_params['id'] = $extend['id'];

        $extendObj->save($process_params);
    }
}
