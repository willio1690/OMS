<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author sunjing@shopex.cn
 * @describe 品骏请求电子面单类
 */
class erpapi_logistics_matrix_pinjun_request_electron extends erpapi_logistics_request_electron
{
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
        
        $delivery     = $sdf['delivery'];

        $shopInfo     = $sdf['shop'];

        $order_bn = array();
        foreach ($delivery['delivery_order'] as $value) {
            $order_bn[$value['order_bn']] = $value['order_bn'];
        }

        $box_infos       = $this->_format_box_infos();
        $goods_info_list = $this->_format_delivery_item($sdf['delivery_item']);

        $order_amount = number_format($delivery['total_amount'],2,".","");
        $params = array(
            'tid'                       =>  $delivery['delivery_bn'].base_shopnode::node_id('ome'),
            'cust_code'                 =>  '',
            'platform_order_sn'         =>  implode('|', $order_bn),
            'order_type'                =>  '1',# 1：快递单 2：配送单 3：上门退
            'sender_name'               =>  $shopInfo['default_sender'],
            'sender_phone'              =>  $shopInfo['tel'] ? $shopInfo['tel'] : $shopInfo['mobile'],
            'sender_mobile'             =>  $shopInfo['mobile'],
            'sender_state'              =>  $shopInfo['province'],
            'sender_city'               =>  $shopInfo['city'],
            'sender_district'           =>  $shopInfo['area'],
            'sender_address'            =>  $shopInfo['address_detail'],
            'receiver_name'             =>  $delivery['ship_name'],
            'receiver_phone'            =>  $delivery['ship_tel'] ? $delivery['ship_tel'] : $delivery['ship_mobile'],
            'receiver_mobile'           =>  $delivery['ship_mobile'],
            'receiver_state'            =>  $this->_formate_receiver_province($delivery['ship_province']),
            'receiver_city'             =>  $delivery['ship_city'],
            'receiver_district'         =>  $delivery['ship_district'],
            'receiver_address'          =>  $delivery['ship_addr'],
            'receiver_code'             =>  $delivery['ship_zip'] ? $delivery['ship_zip'] : '',
            'required_receiver_period'  =>    0,
            //'required_pick_time'        =>  '0',
            'weight'                    =>  $delivery['weight'] ? $delivery['weight'] : 0,
            //'volume'                    =>  '0',
            'transport_day'             =>  '送货时间不限',
            //'is_big'                    =>  '0',
            'product_type'              =>  '1',//1-标准快递、7-标准快运、8-整车运输(暂不开放2-次日达、3-当日达、4-212、5-限时达、6-生鲜递
            'delivery_type'             =>  1,
            'shipment_type'              =>  1,
            //'return_credit'             =>  '',
            //'pay_type'                  =>  1,//0寄付月结，1寄付现结，2到付现结，3：到付月结如果是配送或快递单类型只能传值0,1,2 订单类型为上门退 （包括换货的揽退单 ）时只能传3
            'cod_payment_type'          =>  1,
            'order_amount'              =>  $order_amount*100,//分
            'cod_amount'                =>  '',
            //'valuation_value'           =>  '',
            'carriage'                  =>  '',# 运费，单位:分
            //'settlement_account'        =>  '',
            'is_exchange'               =>  '0',
            //'relation_order_no'         =>   '',
            //'require_delivery_time'     =>   '',
            //'receiver_remark'           =>  '',
            //'total_box'                 =>  '1',

            'goods_info_list'           =>  json_encode($goods_info_list),
            'box_infos'                 =>  json_encode($box_infos),

        );

        // 是否加密
        $is_encrypt = false;
        if (!$is_encrypt) {
            $is_encrypt = kernel::single('ome_security_router',$delivery['shop_type'])->is_encrypt($delivery,'delivery');
        }
        // 云鼎解密
        $gateway = ''; $jst = array ('order_bns' => $delivery['order_bns']);
        if ($is_encrypt) {
            $params['s_node_id']     = $delivery['shop']['node_id'];
            $params['s_node_type']   = $delivery['shop_type'];
            $params['ship_province'] = $delivery['ship_province'];
            $params['ship_city']     = $delivery['ship_city'];
            $params['ship_district'] = $delivery['ship_district'];
            $params['order_bns'] = implode(',', $delivery['order_bns']);

            $gateway = $delivery['shop_type'];
        }

        $back =   $this->requestCall(STORE_PJ_WAYBILL_II_GET, $params,array(),$jst, $gateway);
       
        return $this->backToResult($back, $delivery);
    }


    private function _format_delivery_item($deliveryItems = null) {
        $items = array();
        foreach($deliveryItems as $key=>$item){
            $items[] = array(
                'barcode' => $item['barcode'],
                'name'    => $item['product_name'],
                'num'     => $item['number'],
            );
        }
        return $items;
    }

    private function _format_box_infos($order_bn = ''){
        $box_infos = array();
        $box_infos[] = array(
            'box_no'        =>  1,
           // 'transport_no'  =>  '',
            //'weight'        =>  '',
            //'volume'        =>  '',
        );
        return $box_infos;
    }

    /**
     * backToResult
     * @param mixed $back back
     * @param mixed $delivery delivery
     * @return mixed 返回值
     */
    public function backToResult($back, $delivery){
  
        $data = empty($back['data']) ? '' : json_decode($back['data'], true);

        if($back['rsp'] == 'fail' || empty($data)) {
            return $back['msg'] ? $back['msg'] : false;
        }

        $result = array();
        $logi_no = $data['result']['transportNo'];
        $waybillExtendata = $data['result']['receiveOrgResponse']; #获取大头笔
        $result[] = array(
            'succ'              => $logi_no? true : false,
            'msg'               => '',
            'delivery_id'       => $delivery['delivery_id'],
            'delivery_bn'       => $delivery['delivery_bn'],
            'logi_no'           => $logi_no,
            //'noWayBillExtend'   =>  true,
            'position_no'        =>  $waybillExtendata['sortingCode'],#大头笔编码
            //'position'           =>  $waybillExtendata['position_no'],#大头笔名称
            'package_wd'         => $waybillExtendata['orgCode'],#集包地编码
            'package_wdjc'       => $waybillExtendata['orgName'],#集包地名称
        );

        return $result;
    }
    



    /**
     * recycleWaybill
     * @param mixed $waybillNumber waybillNumber
     * @param mixed $delivery_bn delivery_bn
     * @return mixed 返回值
     */
    public function recycleWaybill($waybillNumber,$delivery_bn = '') {
        $this->title = '品骏_' . $this->__channelObj->channel['logistics_code'] . '取消电子面单';

        $this->primaryBn = $waybillNumber;

        // 判断快递单是否补打
        $bill = app::get('ome')->model('delivery_bill')->dump(array('logi_no'=>$waybillNumber));
        if ($bill) {
            $delivery = app::get('ome')->model('delivery')->dump(array('delivery_id'=>$bill['delivery_id']),'delivery_id,delivery_bn');
        } else {
            $delivery = app::get('ome')->model('delivery')->dump(array('logi_no'=>$waybillNumber),'delivery_id,delivery_bn');
        }

        $params = array(
            'tid' => $delivery['delivery_bn'].base_shopnode::node_id('ome'),
        );

        $callback = array(
            'class'  => get_class($this),
            'method' => 'callback'
        );

        $this->requestCall(STORE_PJ_WAYBILI_CANCEL, $params, $callback);
    }
}
