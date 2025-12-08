<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_logistics_matrix_douyin_request_electron extends erpapi_logistics_request_electron
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
        $this->title     = '抖音-' . $this->__channelObj->channel['logistics_code'] . '获取电子面单';
        $this->timeOut   = 20;
        $this->primaryBn = $sdf['primary_bn'];

        // 发货地址
        $seller = array(
            'contact' => [
                'name'   => $sdf['shop']['default_sender'],
                'phone'  => $sdf['shop']['mobile'] ? : $sdf['shop']['tel'],
            ],
            'address' => [
                "country_code"=> "CHN",
                'province_name' => $sdf['shop']['province'],
                'city_name'     => $sdf['shop']['city'],
                'detail_address'   => $sdf['shop']['address_detail'],
                'district_name' => $sdf['shop']['area'],
                'street_name' => $sdf['shop']['street'],
            ]
        );

        $arrDelivery = $sdf['delivery'];
        $deliveryBnKey = array();
        $TradeOrderInfoDto = array();
        $corp = app::get('ome')->model('dly_corp')->getList('prt_tmpl_id, protect, protect_rate, minprice,type, channel_id', array('corp_id'=>$sdf['delivery'][0]['logi_id']), 0, 1);
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
                if($is_encrypt && $delivery['shop_type'] == "luban") {
                    $encryptData = [
                        'shop_id' => $delivery['shop']['shop_id'],
                        'order_bn' => reset($delivery['order_bns']),
                        'ship_name' => $delivery['ship_name'],
                        'ship_tel' => $delivery['ship_tel'],
                        'ship_mobile' => $delivery['ship_mobile'],
                        'ship_addr' => $delivery['ship_addr'],
                    ];
                    $originalEncrypt = kernel::single('ome_security_luban')->get_encrypt_body($encryptData, 'delivery');
                    $originalEncrypt = json_decode($originalEncrypt['data'], 1);
                    $originalEncrypt = current($originalEncrypt);
                    foreach ($originalEncrypt as $dk => $dv) {
                        $delivery[$dk] = $dv;
                        $is_encrypt = false;
                    }
                }elseif ($is_encrypt && $delivery['shop_type'] == "yunji4fx"){
                    $oaidArrOrder = app::get('ome')->model('orders')->getList('order_id', ['order_bn'=>$delivery['order_bns']]);
                    $original = app::get('ome')->model('order_receiver')->db_dump(['order_id'=>current($oaidArrOrder)['order_id']], 'encrypt_source_data');
                    if($original) {
                        $encrypt_source_data = json_decode($original['encrypt_source_data'], 1);
                        if ($encrypt_source_data['external_channel_code'] == 'DYXD') {
                            $delivery['delivery_bn'] = $encrypt_source_data['channel_order_no'];
                            $delivery['ship_addr'] = $encrypt_source_data['receiver_address_index'];
                            $delivery['ship_mobile'] = $encrypt_source_data['receiver_mobile_index'];
                            $delivery['ship_name'] = $encrypt_source_data['buyer_name_index'];
                            $delivery['ship_tel'] = $encrypt_source_data['receiver_phone_index'];
                            $is_encrypt = false;
                        }
                    }
                } elseif ($is_encrypt && $delivery['shop_type'] == "haoshiqi") {
                    $oaidArrOrder = app::get('ome')->model('orders')->getList('order_id', ['order_bn' => $delivery['order_bns']]);
                    $original     = app::get('ome')->model('order_receiver')->db_dump(['order_id' => current($oaidArrOrder)['order_id']], 'encrypt_source_data');
                    if ($original) {
                        $encrypt_source_data = json_decode($original['encrypt_source_data'], 1);
                        if ($encrypt_source_data['is_consignee_encrypt'] && $encrypt_source_data['douyin_open_address_id']) {
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
            $jst['order_bns'] = array_merge($jst['order_bns'], $delivery['order_bns']);
            $bnKey = $delivery['delivery_bn'];
            $main_order_no = '';
            kernel::single('wms_event_trigger_logistics_data_electron_common')->checkChildRqOrdNo($delivery['delivery_bn'], $main_order_no, $bnKey);
            $deliveryBnKey[$bnKey] = $delivery;

            $arrItem = array();
            foreach($delivery['package_items'] as $pVal) {
                $arrItem[] = array(
                    'item_count' => $pVal['count'],
                    'item_name'  => $this->charFilter($pVal['item_name'])
                );
            }
            
            list($logisticsServices, $productType, $monthAccount) = $this->_getLogisticsServices($corp[0], $delivery);
            // if ($monthAccount = $logisticsServices['monthly_account']) {
            //     unset($logisticsServices['monthly_account']);
            // }
            // foreach ($logisticsServices as $lsk => $lsv) {
            //     if($lsv['service_code'] == 'PRODUCT-TYPE') {
            //         $productType = $lsv['service_value'];
            //         unset($logisticsServices[$lsk]);
            //         break;
            //     }
            // }
            $order_bn = rtrim(current($delivery['order_bn']), 'A');
            $tmp = array(
                'order_id'          => $order_bn ? : $delivery['delivery_bn'],
                'pack_id'           => $bnKey,
                'items'                => $arrItem,
                'receiver_info' => array(
                    'contact' =>[
                        'mobile' => $delivery['ship_mobile'],
                        'name'   => $delivery['ship_name'],
                        'phone'  => $delivery['ship_tel']
                    ],
                    'address' => [
                        'country_code'     => 'CHN',
                        'city_name'     => $delivery['ship_city'],
                        'detail_address'   => $delivery['ship_addr'],
                        'district_name' => $delivery['ship_district']?$delivery['ship_district']:'区',
                        'province_name' => $delivery['ship_province'],
                    ]
                ),
                'net_info'  => array(
                    'monthly_account'   => $monthAccount
                ),
                // 新增解密字段
                'order_bns'    => implode(',', $delivery['order_bns']),

            );
            
            //四级地区(镇)
            if($delivery['ship_town']){
                $tmp['receiver_info']['address']['town_name'] = $delivery['ship_town'];
            }
            
            if($productType) {
                $tmp['product_type'] = $productType;
            }
            if($logisticsServices) {
                $tmp['service_list'] = array_values($logisticsServices);
            }
            $TradeOrderInfoDto[] = $tmp;
        }

        $params = array(
            'sender_info' => json_encode($seller),
            'order_infos' => json_encode($TradeOrderInfoDto),
        );
        $userData = app::get('ome')->model('shop')->dump(array('shop_id'=>$delivery['shop']['shop_id']),'addon');
        $userId = $userData['addon']['user_id'];
        if($userId) {
            $params['user_id'] = $userId;
        }
        // 加密请求虎符
        $gateway = '';
        if ($is_encrypt) {
            $params['s_node_id']    = $delivery['shop']['node_id'];
            $params['s_node_type']  = $delivery['shop_type'];

            $gateway = $delivery['shop_type'];
        }

        $result = $this->requestCall(STORE_WAYBILL_GET, $params,  array(), $jst, $gateway);

        $returnResult = $this->backToResult($result, $deliveryBnKey);

        return $returnResult;
    }

    private function _getLogisticsServices($corp, $delivery){
        $logisticsServices = array(); $monthAccount = ''; $productType = '';
        if($this->__channelObj->channel['service_code']) {
            $serviceCode = json_decode($this->__channelObj->channel['service_code'], 1);

            foreach ($serviceCode as $k => $v) {
                if(in_array($k, ['COD', 'SVC-COD'])) {
                    if ($delivery['is_cod'] == 'true' && $v['value'] == '1') {
                        $logisticsServices[] = [
                            'service_code' => $k,
                            'service_value' => json_encode([
                                'value' => $delivery['receivable']
                            ]) 
                        ];
                    }
                    continue;
                }
                if(in_array($k, ['INSURE', 'SVC-INSURE'])) {
                    if ($corp['protect'] == 'true' && $v['value'] == '1') {
                        $protectValue = max($delivery['total_amount'] * $corp['protect_rate'], $corp['minprice']);
                        $logisticsServices[] = [
                            'service_code' => $k,
                            'service_value' => json_encode([
                                'value' => $protectValue
                            ])
                        ];
                    }
                    continue;
                }
                //月结号
                if($k == 'monthly_account') {
                    $monthAccount = $v['value'];
                    continue;
                }

                if ($k == 'PRODUCT-TYPE'){
                    $productType = $v['value'];
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
                        'service_value' => json_encode([
                            'value' => $v['value']
                        ])
                    ];
                }
            }
        }
        return [$logisticsServices, $productType, $monthAccount];
    }

    private function backToResult($ret, $deliveryBnKey){
        $waybill = empty($ret['data']) ? array() : json_decode($ret['data'], true);
        if(empty($waybill) || $ret['rsp'] == 'fail') {
            return $ret['msg'] ? $ret['msg'] : false;
        }
        $result = array();
        foreach ($waybill['ebill_infos'] as $val) {
            $deliveryBn = trim($val['pack_id']);
            $delivery = $deliveryBnKey[$deliveryBn];
            $position = $val['short_address_name'];
            $position_no = $val['short_address_code'] ? : $val['sort_code'];
            $packageWdjc = $val['package_center_name'];
            $packageWd = $val['package_center_code'];

            $result[] = array(
                'succ' => $val['track_no'] ? true : false,
                'msg' => '',
                'delivery_id' => $delivery['delivery_id'],
                'delivery_bn' => $delivery['delivery_bn'],
                'logi_no' => $val['track_no'],
                'mailno_barcode' => '',
                'qrcode' => '',
                'position' => trim($position),
                'position_no' => $position_no,
                'sort_code' => $val['sort_code'],
                'package_wdjc' => (string) $packageWdjc,
                'package_wd' => (string) $packageWd,
                'print_config' => '',
                'json_packet' => $val['extra_resp'],
            );
        }
        foreach ($waybill['err_infos'] as $val) {
            $deliveryBn = trim($val['pack_id']);
            $delivery = $deliveryBnKey[$deliveryBn];
            $result[] = array(
                'succ' => false,
                'msg' => $val['err_msg'],
                'delivery_id' => $delivery['delivery_id'],
                'delivery_bn' => $delivery['delivery_bn'],
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
        app::get('logisticsmanager')->model('waybill')->update(array('status'=>2,'create_time'=>time()),array('waybill_number'=>$waybillNumber));
        $this->title = '抖音_' . $this->__channelObj->channel['logistics_code'] . '取消电子面单';
        $this->primaryBn = $waybillNumber;
        $params = array(
            'track_no' => $waybillNumber
        );
        $callback = array(
            'class' => get_class($this),
            'method' => 'callback'
        );
        $this->requestCall(STORE_WAYBILL_CANCEL, $params, $callback);
    }

    public function getWaybillISearch($sdf = array())
    {
        $params = array();

        $title = '抖音订购地址获取';

        $result = $this->__caller->call(STORE_WAYBILL_ADRESS,$params,array(),$title, 6, $this->__channelObj->channel['logistics_code']);
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
        foreach ($data['netsites'] as $info) {
            if($info['amount'] < $process_params['allocated_quantity']) {
                continue;
            }
            $process_params = array(
                'cancel_quantity'    => $info['cancelled_quantity'] > 0 ? $info['cancelled_quantity'] : 0,
                'allocated_quantity' => $info['amount'],
                'province'           => $info['sender_address'][0]['province_name'],
                'city'               => $info['sender_address'][0]['city_name'],
                'area'               => $info['sender_address'][0]['district_name'],
                'street'             => $info['sender_address'][0]['street_name'],
                'address_detail'     => $info['sender_address'][0]['detail_address'],
                'channel_id'         => $this->__channelObj->channel['channel_id'],
                'print_quantity'     => $info['allocated_quantity'] - $info['recycled_quantity'],
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

    /**
     * 获取EncryptPrintData
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function getEncryptPrintData($sdf) {
        $params = array(
            'track_no' => $sdf['logi_no']
        );

        $title = '获取打印数据';

        $result = $this->__caller->call(STORE_WAYBILL_PRINTDATA,$params,array(),$title, 10, $sdf['logi_no']);
        if ($result['rsp'] == 'succ' && $result['data']) {
            $data = json_decode($result['data'], 1);
            foreach ($data['waybill_infos'] as $value) {
                $result['data'] = $value;
                $result['data']['params'] = $data['extend_field'];
            }
            foreach ($data['err_infos'] as $value) {
                $result['msg'] = $value['err_msg'];
            }
        }

        return $result;
    }
}
