<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 216-01-25
 * @describe 京东电子面单
 */
class wms_event_trigger_logistics_data_electron_aikucun extends wms_event_trigger_logistics_data_electron_common
{

    /**
     * 获取DirectSdf
     * @param mixed $arrDelivery arrDelivery
     * @param mixed $arrBill arrBill
     * @param mixed $shop shop
     * @return mixed 返回结果
     */

    public function getDirectSdf($arrDelivery, $arrBill, $shop) {
        $delivery = $arrDelivery[0];
        if(empty($arrBill)) {
            $this->needRequestId[] = $delivery['delivery_id'];
        } else {
            $this->needRequestId[] = $arrBill[0]['b_id'];
            $delivery['delivery_bn'] = $this->setChildRqOrdNo($delivery['delivery_bn'], $arrBill[0]['b_id']);
        }

        $dOrder = $this->getDeliveryOrder($this->needRequestId);
        $order_bn = $dOrder[0]['order_bn'];
        $shop_type = $dOrder[0]['shop_type'];
        if($shop_type != 'aikucun'){
            return false;
        }

        $primary_bn = uniqid('oet');

        $sdf = parent::getDirectSdf($arrDelivery, $arrBill, $shop);
        $sdf['primary_bn'] = $primary_bn;
        $sdf['order_bn'] = $order_bn;
        $sdf['delivery'] = $delivery;
        return $sdf;
    }





}