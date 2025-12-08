<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 得物
 * Class wms_event_trigger_logistics_data_electron_dewu
 */
class wms_event_trigger_logistics_data_electron_dewu extends wms_event_trigger_logistics_data_electron_common
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
        $delivery  = $arrDelivery[0];
        $primaryBn = $delivery['delivery_bn'];
        if (empty($arrBill)) {
            $this->needRequestId[] = $delivery['delivery_id'];
        } else {
            $this->needRequestId[]   = $arrBill[0]['log_id'];
            $delivery['delivery_bn'] = $this->setChildRqOrdNo($delivery['delivery_bn'], $arrBill[0]['log_id']);
        }
        
        $sdf                   = parent::getDirectSdf($arrDelivery, $arrBill, $shop);
        $sdf['primary_bn']     = $primaryBn;
        $sdf['delivery']       = $delivery;
        $sdf['logistics_code'] = $this->channel['logistics_code'];
        return $sdf;
    }
}