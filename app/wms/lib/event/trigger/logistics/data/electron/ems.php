<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_event_trigger_logistics_data_electron_ems extends wms_event_trigger_logistics_data_electron_common {

    /**
     * 获取DeliverySdf
     * @param mixed $delivery delivery
     * @param mixed $shop shop
     * @return mixed 返回结果
     */
    public function getDeliverySdf($delivery, $shop) {
        $sdf = array();
        $sdf['delivery'] = $delivery;
        $sdf['shop'] = $shop;
        return $sdf;
    }

    /**
     * 获取DirectSdf
     * @param mixed $arrDelivery arrDelivery
     * @param mixed $arrBill arrBill
     * @param mixed $shop shop
     * @return mixed 返回结果
     */
    public function getDirectSdf($arrDelivery, $arrBill, $shop) {
        return false;
    }
}