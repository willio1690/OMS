<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 银联电子面单
 */
class wms_event_trigger_logistics_data_electron_unionpay extends wms_event_trigger_logistics_data_electron_common
{
    /**
     * 获取DirectSdf
     * @param mixed $arrDelivery arrDelivery
     * @param mixed $arrBill arrBill
     * @param mixed $shop shop
     * @return mixed 返回结果
     */

    public function getDirectSdf($arrDelivery, $arrBill, $shop)
    {
        $delivery = $arrDelivery[0];
        $deliveryItems = $this->getDeliveryItems($delivery['delivery_id']);
        if(empty($arrBill)) {
            $this->needRequestId[] = $delivery['delivery_id'];
        } else {
            $this->needRequestId[] = $arrBill[0]['b_id'];
            $delivery['delivery_bn'] = $this->setChildRqOrdNo($delivery['delivery_bn'], $arrBill[0]['b_id']);
        }
        if(empty($shop)){
            $shop_obj = app::get('ome')->model('shop');
            $shop = $shop_obj->dump(array('shop_id' => $delivery['shop_id']));
            $addr_arr = explode(':',$shop['area']);
            $address = explode('/', $addr_arr[1]);
            $shop['province'] = $address[0];
            $shop['city'] = $address[1];
          
        }
        $sdf = parent::getDirectSdf($arrDelivery, $arrBill, $shop);
        $sdf['primary_bn']    = $delivery['delivery_bn'];
        $sdf['delivery']      = $delivery;
        $sdf['shop']          = $shop;
        $sdf['delivery_item'] = $deliveryItems;
        return $sdf;
    }

}