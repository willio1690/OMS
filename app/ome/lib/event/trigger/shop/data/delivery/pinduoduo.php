<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 获取数据
 * Class ome_event_trigger_shop_data_delivery_pinduoduo
 */
class ome_event_trigger_shop_data_delivery_pinduoduo extends ome_event_trigger_shop_data_delivery_common
{
    /** 
     * 获取数据
     * @param $delivery_id
     * @return array
     * @throws Exception
     */
    public function get_sdf($delivery_id)
    {
        $this->__sdf = parent::get_sdf($delivery_id);

        if (!$this->__sdf) {
            return [];
        }

        // 订单拆单判断
        $order = $this->__sdf['orderinfo'];
        $is_split = $this->_is_split_order($delivery_id);
        if ($is_split) {
            // 判断第一单还是最后一单
            $this->_nonsupport_mode_request($delivery_id);

            $delivery = $this->__deliverys[$delivery_id];
            $this->__sdf['is_first_delivery'] = false;

            if ($delivery['delivery_id'] == $this->firstDeliveryId || ($delivery['parent_id'] > 0 && $delivery['parent_id'] == $this->firstDeliveryId)){
                $this->__sdf['is_first_delivery'] = true;
            }
    
            $this->__sdf['is_last_delivery'] = false;
            if ((in_array($delivery['delivery_id'], $this->lastDeliveryId) && $order['ship_status'] == '1') || ($delivery['parent_id'] > 0 && in_array($delivery['parent_id'], $this->lastDeliveryId))) {
                $this->__sdf['is_last_delivery'] = true;
            }

            // 第一单发货单是否回写成功
            if ($this->__sdf['is_first_delivery']){
                $delivery = $this->__deliverys[$delivery_id];
                $shipment = app::get('ome')->model('shipment_log')->getList('deliveryCode,status', [
                    'shopId'=>$delivery['shop_id'], 
                    'orderBn'=>$this->__sdf['orderinfo']['order_bn'],
                    'deliveryCode' => $delivery['logi_no'],
                ]);

                if ($shipment['status'] == 'succ'){
                    $this->__sdf['status_first_delivery'] = 'succ';
                }
            }
            
            //只回写第一张和最后一张
            if (!$this->__sdf['is_first_delivery'] && !$this->__sdf['is_last_delivery']) {
                return [];
            }
    
            //订单已发货时，只需要上传补打运单信息
            if (in_array($delivery_id, $this->lastDeliveryId) && $order['ship_status'] == '1') {
                $this->__sdf['status_first_delivery'] = 'succ';
            }
            
            $this->__sdf['first_delivery_id'] = $this->firstDeliveryId;


    
        }
    
        //获取所有包裹
        $orderDelivery = app::get('ome')->model('delivery')->getAllDeliversOrderId($order['order_id']);
        $delivery_package = [];
        foreach($orderDelivery as $value){
            $package = $this->_get_delivery_package($value['delivery_id']);
            $delivery_package    = array_merge($delivery_package,(array)$package);
        }
        $this->__sdf['delivery_package'] = $delivery_package;
        $this->__sdf['is_split']         = $is_split;

        // 唯一码
        $this->__sdf['serial_number'] = $this->_get_product_serial_sn_imei($delivery_id);
        
        $giftinfo = $this->isGIFT($order['order_id']);

        if($giftinfo && $giftinfo['gift_order_status']){
            $gift_items = $this->_get_gift_items_sdf($delivery_id);
            if($gift_items){
                $this->__sdf['gift_items'] = $gift_items;
                
            }
            $this->__sdf['gift_order_status'] = $giftinfo['gift_order_status'];
        }
        

        return $this->__sdf;
    }


    protected function _get_gift_items_sdf($delivery_id)
    {
        $delivery_items = array();

        $delivery_items_detail = $this->_get_delivery_items_detail($delivery_id);
        $order_objects         = $this->_get_order_objects($delivery_id);
        $gift_items = [];
        foreach ($delivery_items_detail as $key => $value) {
            $order_item = $order_objects[$value['order_obj_id']]['order_items'][$value['order_item_id']];
            $oid = $order_objects[$value['order_obj_id']]['oid'];
            if ($value['item_type'] == 'gift' && $oid) {
              
               $gift_items[] = array(
                   
                    'oid'           => $order_objects[$value['order_obj_id']]['oid'],
                    'logi_no'       => $value['logi_no'] ? $value['logi_no'] : $this->__sdf['logi_no'],
                    'logi_type'     => $value['logi_type'] ? $value['logi_type'] : $this->__sdf['logi_type'],
                    'logi_name'     => $value['logi_name'] ? $value['logi_name'] : $this->__sdf['logi_name'],
                    
                );
               
            } 
        }
        if($gift_items) return $gift_items;
        
    }

    public function isGIFT($order_id){

        //京东变成可发货
        $ordLabelObj = app::get('ome')->model('bill_label');
       
        $bills = $ordLabelObj->dump(array('label_code'=>'SOMS_GIFT_ORDER_STATUS','bill_type'=>'order','bill_id'=>$order_id),'bill_id,extend_info');

        if($bills){
           
            $extend_info = json_decode($bills['extend_info'],true);

            return $extend_info;
        }

        return false;

    }
}
