<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_event_trigger_logistics_data_electron_sf extends wms_event_trigger_logistics_data_electron_common {

    /**
     * 获取DirectSdf
     * @param mixed $arrDelivery arrDelivery
     * @param mixed $arrBill arrBill
     * @param mixed $shop shop
     * @return mixed 返回结果
     */
    public function getDirectSdf($arrDelivery, $arrBill, $shop) {
        $delivery = $arrDelivery[0];
        $deliveryItems = $this->getDeliveryItems($delivery['delivery_id']);
        $totalAmount = 0;
        $dlyOrder = $this->getDeliveryOrder($delivery['delivery_id']);
        foreach ($dlyOrder as $k => $v) {
            $totalAmount += $v['total_amount'];
        }
        $dlyCorp = app::get('ome')->model('dly_corp')->dump(array('corp_id'=>$delivery['logi_id']));
        if(empty($arrBill)) {
            $this->needRequestId[] = $delivery['delivery_id'];
        } else {
            $this->needRequestId[] = $arrBill[0]['b_id'];
            $delivery['delivery_bn'] = $this->setChildRqOrdNo($delivery['delivery_bn'], $arrBill[0]['b_id']);
        }
        $sdf = parent::getDirectSdf($arrDelivery, $arrBill, $shop);
        $sdf['primary_bn']    = $delivery['delivery_bn'];
        $sdf['delivery']      = $delivery;
        $sdf['shop']          = $shop;
        $sdf['total_amount']  = $totalAmount;
        $sdf['dly_corp']      = $dlyCorp;
        $sdf['delivery_item'] = $deliveryItems;
        return $sdf;
    }
}
