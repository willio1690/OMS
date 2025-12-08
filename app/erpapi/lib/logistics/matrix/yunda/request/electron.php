<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2015-12-15
 * @describe 韵达请求电子面单类
 */
class erpapi_logistics_matrix_yunda_request_electron extends erpapi_logistics_request_electron
{
    public $node_type = 'yunda';
    public $to_node = '1273396838';
    public $shop_name = '韵达官方电子面单';
    protected $directNum = 20;

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
        $this->primaryBn = $sdf['primary_bn'];
        $arrDelivery = $sdf['delivery']; //发货单主表信息外，还需要total_amount、delivery_item
        $shop = $sdf['shop'];
        list ($sysAccount, $passWord) = explode('|||', $this->__channelObj->channel['shop_id']);
        $orders = $deliveryBnKey = array();
        foreach($arrDelivery as $delivery) {
            // 是否加密
            if (!$is_encrypt) {
                $is_encrypt = kernel::single('ome_security_router',$delivery['shop_type'])->is_encrypt($delivery,'delivery');
            }
            $jst['order_bns'] = array_merge($jst['order_bns'], $delivery['order_bns']);

            $deliveryBnKey[$delivery['delivery_bn']] = $delivery;
            $receiver = $this->getReceiverInfo($delivery);
            $items = array();
            foreach ($delivery['delivery_item'] as $item) {
                $items[] = array(
                    'name' => $item['product_name'],
                    'number' => $item['number'],
                    'remark' => '',
                );
            }
            $delivery_bn =  $delivery['delivery_bn'] ;
            $orders[] = array(
                'khddh' => $delivery_bn,
                'nbckh' => $sysAccount,
                's_name' => $shop['default_sender'],
                's_company' => $shop['shop_name'],
                's_city' => $shop['city'],
                's_address' => $shop['city'] . $shop['address_detail'],
                's_postcode' => $shop['zip'],
                's_phone' => $shop['tel'],
                's_mobile' => $shop['mobile'],
                's_branch' => '',
                'r_name' => $receiver['name'],
                'r_company' => $receiver['company'],
                'r_city' => $receiver['city'],
                'r_address' => $receiver['city'] . $receiver['address'],
                'r_postcode' => $receiver['postcode'],
                'r_phone' => $receiver['phone'],
                'r_mobile' => $receiver['mobile'],
                'r_branch' => '',
                'weight' => $delivery['net_weight'],
                'size' => '',
                'value' => $delivery['total_amount'],
                'collection_value' => '',//代收金额，暂时不用
                'special' => '',
                'items' => $items,
                'remark' => '',
                'receiver_force' => 0,

                // 新增解密字段
                'order_bns'        => implode(',', $delivery['order_bns']),
            );
        }
        $params = array(
            'orders' => json_encode($orders),
        );

        // 加密请求虎符
        $gateway = '';
        if ($is_encrypt) {
            $params['s_node_id']    = $delivery['shop']['node_id'];
            $params['s_node_type']  = $delivery['shop_type'];

            $gateway = $delivery['shop_type'];
        }

        $ret = $this->requestCall(STORE_YD_ORDERSERVICE, $params,array(),$sdf, $gateway);

        return $this->backToResult($ret, $deliveryBnKey);
    }

    /**
     * backToResult
     * @param mixed $ret ret
     * @param mixed $deliveryBnKey deliveryBnKey
     * @return mixed 返回值
     */
    public function backToResult($ret, $deliveryBnKey){
        $data = empty($ret['data']) ? '' : json_decode($ret['data'], true);
        if(empty($data['responses'])) {
            return $ret['msg'] ? $ret['msg'] : false;
        }
        $result = array();
        foreach ($data['responses'] as $response) {
            $deliveryBn = trim($response['order_serial_no']);
            $delivery = $deliveryBnKey[$deliveryBn];
            $pdf_info = json_decode($response['pdf_info'], true);
            $result[] = array(
                'succ' => $response['mail_no'] ? true : false,
                'msg' => $response['msg'],
                'delivery_id' => $delivery['delivery_id'],
                'delivery_bn' => $delivery['delivery_bn'],
                'logi_no' => $response['mail_no'],
                'mailno_barcode' => $pdf_info[0][0]['mailno_barcode'],
                'qrcode' => $pdf_info[0][0]['qrcode'],
                'position' => $pdf_info[0][0]['position'],
                'position_no' => $pdf_info[0][0]['position_no'],
                'package_wdjc' => $pdf_info[0][0]['package_wdjc'],
                'package_wd' => $pdf_info[0][0]['package_wd'],
                'print_config' => '',
                'json_packet' => $response['pdf_info'],
            );
        }
        $this->directDataProcess($result);
        return $result;
    }

    /**
     * 获取ReceiverInfo
     * @param mixed $delivery delivery
     * @return mixed 返回结果
     */
    public function getReceiverInfo($delivery) {
        if ($this->isMunicipality($delivery['ship_province']) ) {
            $delivery['ship_province'] = str_replace('市', '', $delivery['ship_province']);
            $delivery['ship_province'] .= '市';
        }
        $city = $delivery['ship_province'] . ',' . $delivery['ship_city'] . ',' . $delivery['ship_district'];
        $reciver = array(
            'name' => $delivery['ship_name'],
            'company' => $delivery['ship_name'],
            'city' => $city,
            'address' => $delivery['ship_addr'],
            'postcode' => $delivery['ship_zip'],
            'phone' => $delivery['ship_tel'],
            'mobile' => $delivery['ship_mobile'],
        );
        return $reciver;
    }
}