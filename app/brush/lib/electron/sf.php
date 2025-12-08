<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2015-12-16
 * @describe 发起电子面单请求 每次请求一条
 */
class brush_electron_sf extends brush_electron_abstract{

    /**
     * deliveryToSdf
     * @param mixed $delivery delivery
     * @return mixed 返回值
     */

    public function deliveryToSdf($delivery) {
        $sdf = parent::deliveryToSdf($delivery);
        $delivery = $delivery[0];
        $shop = $this->getChannelExtend();
        $deliveryItems = $this->getDeliveryItems($delivery['delivery_id']);
        $totalAmount = $this->getOrderTotalAmount($delivery['delivery_id']);
        $dlyCorp = $this->getDlyCorp($delivery['logi_id']);
        $sdf['primary_bn']    = $delivery['delivery_bn'];
        $sdf['delivery']      = $delivery;
        $sdf['shop']          = $shop;
        $sdf['total_amount']  = $totalAmount;
        $sdf['dly_corp']      = $dlyCorp;
        $sdf['delivery_item'] = $deliveryItems;

        return $sdf;
    }
}