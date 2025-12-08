<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_logistics_matrix_meituan4bulkpurchasing_request_electron extends erpapi_logistics_request_electron
{
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
        $this->title     = '美团电商-' . $this->__channelObj->channel['logistics_code'] . '获取电子面单';
        $this->timeOut   = 20;
        $this->primaryBn = $sdf['primary_bn'];

        $sdf['order_bns'] = array_column($sdf['order'], 'order_bn');

        $params           = [];
        foreach($sdf['order'] as $v) {
            if($v['shop_type'] == 'meituan4bulkpurchasing' && $v['createway'] == 'matrix') {
                $params['source'] = 1;
                $params['order_view_id'] = $v['order_bn'];
                break;
            }
        }
        if(!$params['source']) {
            $params['source'] = 2;
            $params['third_order_id'] = $sdf['delivery']['delivery_bn'];
        }
        $params['company_code'] = $this->__channelObj->channel['logistics_code']; // 快递公司编码

        $serviceCode = json_decode($this->__channelObj->channel['service_code'], 1);

        foreach ($serviceCode as $k => $v) {
            if ($k == 'MERCHANT_ACCOUNT') {
                $params['merchant_account'] = $v['value'];
                continue;
            }
            if ($k == 'express_type') {
                $params['express_type'] = $v['value'];
                continue;
            }
        }

        $params['sender'] = json_encode([
            'sender_city'     => $sdf['shop']['city'],
            'sender_address_detail'   => $sdf['shop']['address_detail'],
            'sender_town' => $sdf['shop']['area'],
            'sender_province' => $sdf['shop']['province'],
            'sender_street'     => $sdf['shop']['street'],
            'sender_name'    => $sdf['shop']['default_sender'], // 姓名，明文
            'sender_phone'   => $sdf['shop']['mobile'] ? : $sdf['shop']['tel'],
        ]); // 寄件人地址必须和订购关系的地址保持一致

        $params['package_id'] = $sdf['delivery']['delivery_bn'];
        if($params['source'] == 1) {
            $product_info = [];
            foreach ($sdf['delivery_item'] as $k => $value) {
                if(!$value['oid']) {
                    continue;
                }
                $product_info[] = [
                    'spu_id' => $value['shop_goods_id'],
                    'sku_id' => $value['oid'],
                    'product_count' => $value['quantity'],
                ];
            }
            if($product_info) {
                $params['product_info'] = json_encode($product_info);
            }
        } else {
            $receiver = [
                'receiver_city'     => $sdf['delivery']['ship_city'],
                'receiver_address_detail'   => $sdf['delivery']['ship_addr'],
                'receiver_town' => $sdf['delivery']['ship_district'],
                'receiver_province' => $sdf['delivery']['ship_province'],
                'receiver_street'     => '',
                'receiver_name'          => $sdf['delivery']['ship_name'],
                'receiver_phone'         => $sdf['delivery']['ship_mobile'] ? : $sdf['delivery']['ship_tel'],
            ];
            $params['receiver'] = json_encode($receiver);
            $product_info = [];
            foreach ($sdf['delivery_item'] as $k => $value) {
                $product_info[] = [
                    'spu_name' => $value['name'],
                    'product_spec' => $value['bn'],
                    'product_count' => $value['quantity'],
                ];
            }
            $params['product_info'] = json_encode($product_info);
        }

        $result = $this->requestCall(STORE_WAYBILL_GET, $params, array());

        $returnResult = $this->backToResult($result, $sdf['delivery'], $params['source']);

        return $returnResult;
    }

    private function backToResult($ret, $delivery, $source)
    {
        $waybill = empty($ret['data']) ? array() : json_decode($ret['data'], true);
        $waybill = $waybill['data'];
        if (empty($waybill) || $ret['rsp'] == 'fail') {
            return $ret['msg'] ? $ret['msg'] : false;
        }
        $waybill['package_id'] = $delivery['delivery_bn'];
        $waybill['source'] = $source;
        $result = array();
        $result[] = array(
            'succ'           => $waybill['way_bill_id'] ? true : false,
            'msg'            => '',
            'delivery_id'    => $delivery['delivery_id'],
            'delivery_bn'    => $delivery['delivery_bn'],
            'logi_no'        => $waybill['way_bill_id'],
            'mailno_barcode' => '',
            'qrcode'         => '',
            'position'       => '',
            'position_no'    => '',
            'sort_code'      => $waybill['mark'],
            'package_wdjc'   => $waybill['bag_address'],
            'package_wd'     => '',
            'print_config'   => '',
            'json_packet'    => json_encode($waybill),
        );
        $this->directDataProcess($result);
        return $result;
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

        $this->title     = '美团电商_' . $this->__channelObj->channel['logistics_code'] . '取消电子面单';
        $this->primaryBn = $waybillNumber;

        $params = array(
            'way_bill_id' => $waybillNumber,
            'reason'      => '发货单撤销',
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
        $sql = 'SELECT w.waybill_number, e.json_packet FROM sdb_logisticsmanager_waybill w LEFT JOIN sdb_logisticsmanager_waybill_extend e ON (w.id = e.waybill_id) WHERE w.waybill_number = "'.$sdf['logi_no'].'" AND w.channel_id = "' . $this->__channelObj->channel['channel_id'] . '"';
        $row = kernel::database()->selectrow($sql);
        $json_packet = json_decode($row['json_packet'], 1);
        $this->primaryBn = $json_packet['order_view_id'];
        $params = array(
            'order_view_ids' => json_encode([$json_packet['order_view_id']]),
            'package_info'   => json_encode([
                [
                    'order_view_id' => $json_packet['order_view_id'],
                    'package_ids' => [$json_packet['package_id']]
                ]
            ]),
            'source' => $json_packet['source']
        );
        if($sdf['custom_data']) {
            $params['custom_template_data'] = json_encode([
                [
                    'order_view_id' => $json_packet['order_view_id'],
                    'package_id' => $json_packet['package_id'],
                    'data' => $sdf['custom_data']
                ]
            ]);
        }
        return $this->getAccessToken($params);
    }

    /**
     * 获取AccessToken
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function getAccessToken($sdf) {
        $this->title       = '美团电商面单_' . $this->__channelObj->channel['logistics_code'] . '获取签名';
        $this->primaryBn || $this->primaryBn = 'access_token';
        $sdf['pluginKey'] = 'thh_print_plugin';
        $params = [
            "url"=> "https://waimaiopen.meituan.com/api/v1/gw/logistics/label/inner/batchGetInfo",
            'params'=>json_encode($sdf)
        ];
        $return = $this->requestCall(STORE_SF_ACCESS_TOKEN, $params);
        if($return['data']) {
            $return['data'] = json_decode($return['data'], 1);
        }
        return $return;
    }

    public function getWaybillISearch($sdf = array())
    {
        $params = [
            'company_code' => $this->__channelObj->channel['logistics_code'],
        ];

        $title = '查询开通的网点账号信息';

        $result = $this->__caller->call(STORE_STANDARD_XHS_SEARCH, $params, array(), $title, 6, $this->__channelObj->channel['logistics_code']);

        if ($result['rsp'] == 'succ' && $result['data']) {

            $data           = json_decode($result['data'], 1);
        }
        $result['data'] = [];
        $result['msg'] = $result['err_msg'];
        $result['request_logistics_code'] = $this->__channelObj->channel['logistics_code'];
        $result['channel_type']           = $this->__channelObj->channel['channel_type'];

        $_tmp = [];
        if(is_array($data) && is_array($data['data']) && is_array($data['data']['logistics_account_list'])) {
            foreach ($data['data']['logistics_account_list'] as $k => $v) {
                $_tmp[] = [
                    'acct_id'        => $k+1,
                    'delivery_id'    => $v['express_code'],
                    'site_code'      => $v['site_code'],
                    'site_name'      => $v['site_name'],
                    'mobile'         => '',
                    'phone'          => '',
                    'name'           => '',
                    'province_name'  => $v['ship_address']['province'],
                    'city_name'      => $v['ship_address']['city'],
                    'district_name'  => $v['ship_address']['town'],
                    'street_name'    => $v['ship_address']['street'],
                    'detail_address' => $v['ship_address']['detail_address'],
                ];
            }
        }
        $result['data']['account_list'] = $_tmp;

        return $result;
    }

}
