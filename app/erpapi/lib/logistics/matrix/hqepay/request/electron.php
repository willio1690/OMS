<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * User: jintao Date: 2016/7/6
 */
class erpapi_logistics_matrix_hqepay_request_electron extends erpapi_logistics_request_electron
{
    // 隐私面单(脱敏模板)
    private function safetyTemplateSize($company_code='')
    {
        $list = [
            'SF'    =>  'P1302', // 顺丰速运
            'JD'    =>  '1301', // 京东快递
            'JTSD'  =>  '1301', // 极兔速递
            'YD'    =>  'P1301', // 韵达速递
            'YTO'   =>  'P13001', // 圆通速递
            'ZTO'   =>  '1302', // 中通快递
            'STO'   =>  'P1301', // 申通快递
            'EMS'   =>  '1301', // EMS
            'DBL'   =>  '1301', // 德邦快递
            'DBKD'  =>  '1301', // 德邦快递
        ];
        if ($list[$company_code]) {
            return $list[$company_code];
        }
        return '';
    }

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
    public function directRequest($sdf){
        $delivery = $sdf['delivery'];
        $shopInfo = $sdf['shop'];
    
        $serviceCode = array();
        if ($this->__channelObj->channel['service_code']) {
            $serviceCode = @json_decode($this->__channelObj->channel['service_code'], 1);
        }
        if ($serviceCode['SVC-ZMD']['value'] == '1') {
            $main_order_no = '';
            $waybill_cid   = '';
            kernel::single('wms_event_trigger_logistics_data_electron_common')
                ->checkChildRqOrdNo($delivery['delivery_bn'], $main_order_no, $waybill_cid);
            if ($main_order_no) {
                $params = array(
                    "tid"          => $delivery['delivery_bn'],
                    "qty"          => 1,
                    "company_code" => $this->__channelObj->channel['logistics_code'],
                );
                $back   = $this->requestCall(STORE_WAYBILL_SUB_GET, $params, array(), $sdf);
                return $this->backChildToResult($back, $delivery);
            }
        }

        $to_address = $delivery['ship_addr'] ? $delivery['ship_province'] . $delivery['ship_city'] . $delivery['ship_district'] . $delivery['ship_addr'] : '_SYSTEM';
        $from_address = $shopInfo['address_detail'] ? $shopInfo['province'] . $shopInfo['city'] . $shopInfo['area'] . $shopInfo['address_detail'] : '_SYSTEM';
        // $service_list = array(
        //     array('name'=>'SafeMail','value'=>'1'), // 隐私面单
        // );
        $params = array(
            "member_id"=>$delivery['member_id'],
            "send_site"=>"",  # 收件网点标识
            "company_code"=>$this->__channelObj->channel['logistics_code'],  # 物流公司编码
            "logistic_code"=>'',  # 运单号
            "tid"=>$delivery['delivery_bn'],  # 订单号
            "exp_type"=>'1',  # 快递类型 1是标准快件
            "cost"=>'',
            "other_cost"=>'',

            'to_company'=>$this->charFilter($delivery['ship_name']),
            'to_name'=>$delivery['ship_name'],#收货人
            'to_tel'=>$delivery['ship_tel'],
            'to_mobile'=>$delivery['ship_mobile'],
            'to_zip'=>$delivery['ship_zip'],
            'to_province'=>$delivery['ship_province'],
            'to_city'=>$delivery['ship_city'],
            'to_area'=>$delivery['ship_district'],
            'to_address'=> $this->charFilter($to_address),

            'from_company'=>'',
            'from_name'=> $shopInfo['default_sender'] ? $shopInfo['default_sender'] : '_SYSTEM',
            'from_tel'=>$shopInfo['tel'],
            'from_mobile'=> $shopInfo['mobile'],
            'from_zip'=>$shopInfo['zip'],
            'from_province'=>$shopInfo['province'],
            'from_city'=>$shopInfo['city'],
            'from_area'=>$shopInfo['area'],
            'from_address'=>$this->charFilter($from_address),#发件人详细地址


            "start_date"=>'',#上门取货时间段
            "end_date"=>'',
            "weight"=>'',
            "volume"=>'',
            "remark"=>'',
            "qty"=>"",
            // 'service_list'   => json_encode($service_list),
            "goods_list"=>json_encode($this->format_delivery_item($sdf['delivery_item'])),#货品明细信息
            "is_return"=>'0',  # 返回电子面单模板：0-不需要；1-需要
            'is_print_orgin' => '1', // 调用增值服务失败后是否调用原始电子面单
        );

        // 隐私面单
        $params['template_size'] = '';
        $account = explode('|||', $this->__channelObj->channel['shop_id']);
        if (isset($account[5]) && $account[5] == 1) {
            $params['template_size'] = $this->safetyTemplateSize($params['company_code']);
        }

        $service_list = [];
        $serviceCode  = @json_decode($this->__channelObj->channel['service_code'], 1);
        // 保价
        if ($serviceCode['INSURE'] && $serviceCode['INSURE']['value'] == '1') {
            $corp = app::get('ome')->model('dly_corp')->getList('prt_tmpl_id, protect, protect_rate, minprice', array('corp_id'=>$delivery['logi_id']), 0, 1);
            if ($corp['protect'] == 'true') {
                $protectValue = max($delivery['total_amount'] * $corp['protect_rate'], $corp['minprice']);
                $service_list[] = array('name' => $serviceCode['INSURE']['code'], 'value' => sprintf('%.2f', $protectValue));
            }
        }
        // 代收货款
        if ($serviceCode['COD'] && $serviceCode['COD']['value'] == '1') {
            if ($delivery['is_cod'] == 'true') {
                $service_list[] = array(
                    'name'          =>  $serviceCode['COD']['code'], 
                    'value'         =>  sprintf('%.2f', $delivery['receivable']),
                    'customer_id'   =>  $account[3] ? $account[3] : '' // 月结卡号
                );
            }
        }

        $params['service_list'] = json_encode($service_list);

        // 是否加密
        $is_encrypt = false;
        if (!$is_encrypt) {
            $is_encrypt = kernel::single('ome_security_router',$delivery['shop_type'])->is_encrypt($delivery,'delivery');
        }
        // 云鼎解密
        $gateway = ''; $jst = array ('order_bns' => $delivery['order_bns']);
        if ($is_encrypt) {
            $params['s_node_id']    = $delivery['shop']['node_id'];
            $params['s_node_type']  = $delivery['shop_type'];
            // 新增解密字段
            $params['order_bns']    = implode(',', $delivery['order_bns']);
            $gateway = $delivery['shop_type'];
        }

        $back =   $this->requestCall(STORE_HQEPAY_ORDERSERVICE, $params,array(),$sdf, $gateway);

        return $this->backToResult($back, $delivery);
    }
    #获取货物名称
    /**
     * format_delivery_item
     * @param mixed $deliveryItems deliveryItems
     * @return mixed 返回值
     */
    public function format_delivery_item(&$deliveryItems = null) {
        $items = array();
        foreach($deliveryItems as $key=>$item){
            $items[$key]['bn'] = str_replace('+', ' ',$item['bn']);
            $items[$key]['name'] = str_replace('+', ' ',$item['product_name']);
            $items[$key]['qty'] = $item['number'];
        }
        return $items;
    }
    /**
     * backToResult
     * @param mixed $back back
     * @param mixed $delivery delivery
     * @return mixed 返回值
     */
    public function backToResult($back, $delivery){
        $data = empty($back['data']) ? '' : json_decode($back['data'], true);
        if(empty($data['Order'] ['LogisticCode'])){
            $msg = $back['msg'];
        }
        $json_packet = array(
            'ReceiverSafePhone' => (string)$data['ReceiverSafePhone'],
            'DialPage'          => (string)$data['DialPage'],
            'MarkDestination'   => (string)$data['Order']['MarkDestination'],//四段码
            'SortingCode'       =>  (string)$data['Order']['SortingCode'],//末端分间编码
        );
        $result = array();
        $result[] = array(
            'succ' => $data['Order'] ['LogisticCode']? true : false,
            'msg' => $msg,
            'delivery_id' => $delivery['delivery_id'],
            'delivery_bn' => $delivery['delivery_bn'],
            'logi_no' => $data['Order'] ['LogisticCode'],
            'position'     =>  $data['Order'] ['DestinatioName']?$data['Order'] ['DestinatioName']:'',
            'position_no'  =>  $data['Order'] ['DestinatioCode']?$data['Order'] ['DestinatioCode']:'',
            'package_wdjc' => $data['Order']['PackageName']?$data['Order']['PackageName']:'',
            'package_wd'   => $data['Order']['PackageCode']?$data['Order']['PackageCode']:'',
            'json_packet'  => json_encode($json_packet),
        );
        $this->directDataProcess($result);
        return $result;
    }
    
    /**
     * 存储子母单
     * @param $back
     * @param $delivery
     * @return array
     * @date 2025-08-20 下午3:17
     */
    public function backChildToResult($back, $delivery)
    {
        $data = empty($back['data']) ? '' : json_decode($back['data'], true);
        if (empty($data['SubOrders'])) {
            $msg = $back['msg'] ?: $back['err_msg'];
            return $msg ? $msg : false;
        }
        
        $result = [];
        if (!empty($data['SubOrders'])) {
            foreach ($data['SubOrders'] as $logiNo) {
                $result[] = array(
                    'succ'        => $data['Order']['LogisticCode'] ? true : false,
                    'msg'         => '',
                    'delivery_id' => $delivery['delivery_id'],
                    'delivery_bn' => $delivery['delivery_bn'],
                    'logi_no'     => $logiNo,
                );
            }
        }
        $this->directDataProcess($result);
        return $result;
    }
}
