<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2022/6/29 18:09:55
 * @describe
 */
class erpapi_shop_matrix_tmall_request_maochao_order extends erpapi_shop_request_order {

    protected function getReceivedParams($sdf) {
        $order = $sdf['order'];
        $orderExtend = $sdf['order_extend'];
        $extend_field = @json_decode($orderExtend['extend_field'], 1);
        $param = [
            "supplier_id"=> $extend_field['supplierId'],
            "biz_order_code"=> $order['order_bn'],
            "business_model"=> $extend_field['businessModel'],
        ];
        return [SHOP_SUPPLIER_ORDER_CONFIRM, $param];
    }

    /**
     * lackApply
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function lackApply($sdf){
        $order = $sdf['order'];
        $orderExtend = $sdf['order_extend'];
        $order_objects = $sdf['order_objects'];
        $extend_field = @json_decode($orderExtend['extend_field'], 1);
        $out_of_stock_items = [];
        foreach ($order_objects as $key => $value) {
            $out_of_stock_items[] = [
                'lack_quantity' => $value['out_stock'],
                'sub_order_code' => $value['oid'],
                'sc_item_id' => $value['shop_goods_id'],
            ];
        }
        $params = [
            "supplier_id"=> $extend_field['supplierId'],
            "biz_order_code"=> $order['order_bn'],
            "out_biz_id"=> $order['order_bn'],
            "out_of_stock_items"=> json_encode($out_of_stock_items),
            "out_of_stock_reason"=> "没货了",
        ];
        $response = $this->__caller->call(SHOP_SUPPLIER_ORDER_LACK_APPLY,$params,[],'订单缺货申请',30,$order['order_bn']);

        return $response;
    }

    /**
     * reject
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function reject($sdf){
        $order = $sdf['order'];
        $delivery = $sdf['delivery'];
        $corp = $sdf['corp'];
        $branch = $sdf['branch'];
        $reverse_type = $sdf['reverse_type'];
        $orderExtend = $sdf['order_extend'];
        $return_address = $sdf['return_address'];
        $order_objects = $sdf['order_objects'];
        $extend_field = @json_decode($orderExtend['extend_field'], 1);
        $receiver_info = [
            'receiver_mobile' => $return_address['mobile_phone'],
            'receiver_name' => $return_address['contact_name'],
            'receiver_address' => $return_address['addr'],
            'receiver_area' => $return_address['country'],
            'receiver_city' => $return_address['city'],
            'receiver_province' => $return_address['province'],
            'receiver_country' => '中国',
            'receiver_zip_code' => $return_address['zip_code'] ? : '000000',
        ];
        $sender_name = $order['ship_name'];
        if($index = strpos($sender_name, '>>')) {
            $sender_name = substr($sender_name, 0, $index);
        }
        list(,$area,) = explode(':', $order['ship_area']);
        $area = explode('/', $area);
        $sender_info = [
            'sender_name' => $sender_name,
            'sender_area' => $area[2],
            'sender_city' => $area[1],
            'sender_province' => $area[0],
            'sender_country' => '中国',
            'sender_zip_code' => $order['ship_zip'] ? : '000000',
        ];
        $order_items = [];
        foreach ($order_objects as $key => $value) {
            $order_items[] = [
                'erp_order_line' => $value['obj_id'],
                'plan_return_quantity' => $value['quantity'],
                'sub_order_code' => $value['oid'],
                'sc_item_id' => $value['shop_goods_id'],
            ];
        }
        $params = [
            "supplier_id"=> $extend_field['supplierId'],
            "biz_order_code"=> $order['order_bn'],
            "out_biz_id"=> $delivery['delivery_bn'],
            'tms_service_code'=> $corp['type'],
            'tms_order_code'=> $delivery['logi_no'],
            'reverse_type'=> $reverse_type,
            'store_code'=> $branch['branch_bn'],
            'receiver_info'=> json_encode($receiver_info),
            'sender_info'=> json_encode($sender_info),
            'order_items'=> json_encode($order_items),
        ];
        $response = $this->__caller->call(SHOP_SUPPLIER_ORDER_REJECT_APPLY,$params,[],'订单拒收创建',30,$order['order_bn']);

        return $response;
    }
}