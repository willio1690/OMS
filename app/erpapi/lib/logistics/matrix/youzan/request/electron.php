<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_logistics_matrix_youzan_request_electron extends erpapi_logistics_request_electron
{
    // 只能一单一单取
    protected $directNum = 1;

    /**
     * bufferRequest
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function bufferRequest($sdf)
    {
        return $this->directNum;
    }

    /**
     * directRequest
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function directRequest($sdf)
    {
        $this->title     = $this->__channelObj->channel['logistics_code'] . '获取电子面单';
        $this->timeOut   = 20;
        $this->primaryBn = $sdf['primary_bn'];

        $params = $this->__format_delivery($sdf);

        $result = $this->requestCall(STORE_WAYBILL_GET, $params,  [], $params['order_bns'], $sdf['gateway']);

        return $this->backToResult($result, array_column($sdf['delivery'], null, 'delivery_bn'));
    }

    private function backToResult($ret, $deliveryBnKey){
        $data = empty($ret['data']) ? [] : json_decode($ret['data'], true);

        // 失败处理
        if($data['success'] == false) {
            return $data['message'];
        }

        $result = [];
        foreach ($data['data'] as $val) {
            $deliveryBn = trim($val['apply_id']);
            $delivery = $deliveryBnKey[$deliveryBn];

            $main_order_no = $waybill_cid = '';
            $reprint = kernel::single('wms_event_trigger_logistics_data_electron_common')->checkChildRqOrdNo(
                $delivery['delivery_bn'], 
                $main_order_no, 
                $waybill_cid
            );

            if(!$reprint && $val['parent_express_no']){
                $logi_no = $val['parent_express_no'];
            }else{
                $logi_no = $val['express_no'];
            }

            $result[] = array(
                'succ'          => $val['success'] == true ? true : false,
                'msg'           => $val['message'],
                'delivery_id'   => $delivery['delivery_id'],
                'delivery_bn'   => $delivery['delivery_bn'],
                'logi_no'       => $logi_no,
                'json_packet'   => $val['print_data'],
            );
        }
        $this->directDataProcess($result);

        return $result;
    }

    private function _getCainiaoUrl($corp_id) 
    {
        // 模板地址
        $corp = app::get('ome')->model('dly_corp')->dump($corp_id, 'prt_tmpl_id, protect, protect_rate, minprice, channel_id');
        if($corp['channel_id'] != $this->__channelObj->channel['channel_id']) {
            $prtTmpl = app::get('ome')->model('dly_corp_channel')->db_dump([
                    'channel_id' => $this->__channelObj->channel['channel_id'], 
                    'corp_id'    => $corp_id
            ], 'prt_tmpl_id');

            if($prtTmpl) {
                $corp['prt_tmpl_id'] = $prtTmpl['prt_tmpl_id'];
            }
        }

        $templateObj = app::get("logisticsmanager")->model('express_template');
        $printTpl = $templateObj->dump(['template_id' => $corp['prt_tmpl_id']]);
        
        return $printTpl['template_data'];
    }

    /**
     * __format_delivery
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function __format_delivery($sdf = [])
    {
        // 模板地址
        $template_url = $this->_getCainiaoUrl($sdf['delivery'][0]['logi_id']);

        $addon = $this->__channelObj->channel['addon'];

        $data = [];
        $data['company_code'] = $this->__channelObj->channel['logistics_code'];
        $data['payment_type'] = $addon['payment_type'];
        $data['sender'] = [
            'mobile' => $sdf['shop']['mobile'],
            'name' => $sdf['shop']['default_sender'],
            'address' => [
                'county' => $sdf['shop']['area'],
                'city' => $sdf['shop']['city'],
                'province' => $sdf['shop']['province'],
                'address' => $sdf['shop']['address_detail'],
            ],
            
        ];
        $data['sender'] = json_encode($data['sender'], JSON_UNESCAPED_UNICODE);

        // 品牌编码，顺丰必传
        if($this->__channelObj->channel['service_code']) {
            $serviceCode = @json_decode($this->__channelObj->channel['service_code'], 1);
            if ($serviceCode['brand_code'] && $serviceCode['brand_code']['value']) {
                $data['brand_code'] = $serviceCode['brand_code']['value'];
            }
        }

        $trade_order_info_list = [];
        $is_encrypt = false;
        foreach ($sdf['delivery'] as $delivery) {
            $need_decrypt = $is_encrypt = kernel::single('ome_security_router',$delivery['shop_type'])->is_encrypt($delivery,'delivery');
            // 是否加密
            if ($is_encrypt && $delivery['shop_type'] == "youzan") {
                $encryptData = [
                    'shop_id'       => $delivery['shop']['shop_id'],
                    'order_bn'      => reset($delivery['order_bns']),
                    'ship_name'     => $delivery['ship_name'],
                    'ship_tel'      => $delivery['ship_tel'],
                    'ship_mobile'   => $delivery['ship_mobile'],
                    'ship_addr'     => $delivery['ship_addr'],
                ];

                $originalEncrypt = kernel::single('ome_security_youzan')->get_encrypt_body($encryptData, 'delivery');
                $originalEncrypt = json_decode($originalEncrypt['data'], 1);

                $originalEncrypt = current($originalEncrypt);
                foreach ($originalEncrypt as $dk => $dv) {
                    $delivery[$dk] = $dv;

                    $need_decrypt = false;
                }
            }


            if ($need_decrypt){
                $data['s_node_id']    = $delivery['shop']['node_id'];
                $data['s_node_type']  = $delivery['shop']['node_type'];
                $data['order_bns']    = implode(',', $delivery['order_bns']);
                $data['gateway']      = $delivery['shop']['node_type'];
            }

            $items = [];
            foreach($delivery['package_items'] as $item) {
                $items[] = [
                    'name'  => $item['item_name'],
                    'count' => $item['count'],
                ];
            }

            $trade = [
                'tid' => array_shift($delivery['order_bns']),
                'recipient' => [
                    'name' => $delivery['ship_name'],
                    'mobile' => $delivery['ship_mobile'],
                    'address' => [
                        'address' => $delivery['ship_addr'],
                        'city' => $delivery['ship_city'],
                        'county' => $delivery['ship_district'],
                        'province' => $delivery['ship_province'],
                    ],
                ],
                'apply_id' => $delivery['delivery_bn'],
                'template_url' => $template_url,
                'package_info' => [
                    'weight' => (int)$delivery['net_weight'],
                    'items' => $items,
                ],
                'merge_retrieval_order_nos' => $delivery['order_bns'],
                'yz_order' => false,
                'use_param_recipient' => !$is_encrypt ? true : false,
            ];
            // $delivery['shop_type'] == 'youzan' && $delivery['delivery_order'][0]['createway'] == 'matrix' ? true : 
            if ($delivery['shop_type'] == 'youzan' && $delivery['delivery_order'][0]['createway'] == 'matrix') {
                $trade['yz_order'] = true;
            }
            
            if ($delivery['shop_type'] == 'youzan' && $delivery['delivery_order'][0]['order_source'] == 'platformexchange') {
                $trade['yz_order'] = true;
            }

            $trade_order_info_list[] = $trade;
        }

        $data['trade_order_info_list'] = json_encode($trade_order_info_list, JSON_UNESCAPED_UNICODE);

        return $data;
    }

    /**
     * recycleWaybill
     * @param mixed $waybillNumber waybillNumber
     * @param mixed $delivery_bn delivery_bn
     * @return mixed 返回值
     */
    public function recycleWaybill($waybillNumber, $delivery_bn = '')
    {
        app::get('logisticsmanager')->model('waybill')->update(array('status' => 2, 'create_time' => time()), array('waybill_number' => $waybillNumber));

        $this->title     = $this->__channelObj->channel['logistics_code'] . '取消电子面单';

        $this->primaryBn = $waybillNumber;

        $cpCode = $this->__channelObj->channel['logistics_code'];

        $params = array(
            'express_no' => $waybillNumber,
            'express_id' => $cpCode,
        );

        $callback = array(
            'class'  => get_class($this),
            'method' => 'callback',
        );

        $this->requestCall(STORE_WAYBILL_CANCEL, $params, $callback);
    }

    /**
     * 获取EncryptPrintData
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function getEncryptPrintData($sdf)
    {
        $cpCode = $this->__channelObj->channel['logistics_code'];

        $params = array(
            'express_no' => $sdf['logi_no'],
            'express_id' => $cpCode,
        );

        $title = '获取打印数据';

        $result = $this->__caller->call(STORE_WAYBILL_PRINTDATA, $params, array(), $title, 10, $sdf['logi_no']);

        if ($result['rsp'] == 'succ' && $result['data']) {
            $data = json_decode($result['data'], 1);

            $result['data'] = $data;
        } else {
            $result['msg'] = $result['err_msg'];
        }

        return $result;
    }

    public function getWaybillISearch($sdf = array())
    {
        return $this->succ();
    }


}
