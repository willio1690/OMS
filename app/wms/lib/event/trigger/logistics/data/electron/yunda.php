<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_event_trigger_logistics_data_electron_yunda extends wms_event_trigger_logistics_data_electron_common
{

    /**
     * 获取DirectSdf
     * @param mixed $arrDelivery arrDelivery
     * @param mixed $arrBill arrBill
     * @param mixed $shop shop
     * @return mixed 返回结果
     */
    public function getDirectSdf($arrDelivery, $arrBill, $shop) {
        $deliveryOrder = $this->getDeliveryOrder($this->needRequestDeliveryId);
        $dlyIdOrder = array();
        foreach($deliveryOrder as $val) {
            $dlyIdOrder[$val['wms_delivery_id']][] = $val;
        }
        $deliveryExtend = array();
        foreach($arrDelivery as $delivery) {
            $totalAmount = 0;
            foreach ($dlyIdOrder[$delivery['delivery_id']] as $k => $v) {
                $totalAmount += $v['total_amount'];
            }
            $deliveryItems = $this->getDeliveryItems($delivery['delivery_id']);//发货单明细
            $tmpDelivery = $delivery;
            $tmpDelivery['total_amount'] = $totalAmount;
            $tmpDelivery['delivery_item'] = $deliveryItems;
            $deliveryExtend[] = $tmpDelivery;
            if(empty($arrBill)) {
                $this->needRequestId[] = $delivery['delivery_id'];
            } else {
                $dlyExtend = $deliveryExtend[0];
                $deliveryExtend = array();
                foreach($arrBill as $bill) {
                    $tmp = $dlyExtend;
                    $this->needRequestId[] = $bill['b_id'];
                    $tmp['delivery_bn'] = $this->setChildRqOrdNo($dlyExtend['delivery_bn'], $bill['b_id']);
                    $deliveryExtend[] = $tmp;
                }
                break;
            }
        }
        $primary_bn = uniqid('oey');
        $sdf = parent::getDirectSdf($arrDelivery, $arrBill, $shop);
        $sdf['primary_bn'] = $primary_bn;
        $sdf['shop']       = $shop;
        $sdf['delivery']   = $deliveryExtend;
        return $sdf;
    }
}