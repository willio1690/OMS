<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

trait ome_event_trigger_logistics_data_electron_traitcommon {

    public function preDealDelivery($arrDeliveryId) {

        $arrDelivery = app::get('ome')->model('delivery')->getList('*', array('delivery_id'=>$arrDeliveryId));
        $billObj = app::get('ome')->model('delivery_bill');
        $objWaybill = app::get('logisticsmanager')->model('waybill');
        $needRequest = $hasDelivery = array();
        foreach($arrDelivery as $delivery) {
            $arrBill = $billObj->dump(array('delivery_id'=>$delivery['delivery_id'],'type'=>'1','status'=>'0'),'logi_no');
            $logi_no = $arrBill['logi_no'];
            $hasDelivery[] = $delivery['delivery_id'];
            $filter = array('channel_id' => $this->channel['channel_id'], 'waybill_number' => $logi_no);
            if(!$this->checkLogisticsChannel($delivery['logi_id'])) {
                $this->directRet['fail'][] = array(
                    'delivery_id' => $delivery['delivery_id'],
                    'delivery_bn' => $delivery['delivery_bn'],
                    'msg' => '该发货单已经切换物流公司了'
                );
                continue;
            }
            if (!empty($logi_no) && $objWaybill->dump($filter)) {
                $this->directRet['succ'][] = array(
                    'logi_no' => $logi_no,
                    'delivery_id' => $delivery['delivery_id'],
                    'delivery_bn' => $delivery['delivery_bn']
                );
            } else {
                $needRequest[] = $delivery;
                $this->needRequestDeliveryId[] = $delivery['delivery_id'];
            }
        }
        $noDelivery = array_diff($arrDeliveryId, $hasDelivery);
        if($noDelivery) {
            foreach($noDelivery as $val) {
                $this->directRet['fail'][] = array(
                    'delivery_id' => $val,
                    'msg' => '没有该发货单'
                );
            }
        }
        return $needRequest;
    }

    //获取两条发货单明细
    public function getDeliveryItems($delivery_id) {
        static $deliveryItems = array();
        if(!$deliveryItems[$delivery_id]) {
            $deliveryItems[$delivery_id] = app::get('ome')->model('delivery_items')->getList('*', array('delivery_id' => $delivery_id),0,2);
        }
        return $deliveryItems[$delivery_id];
    }

    public function getDeliveryIdByWms($deliveryId){
        $deliveryId = is_array($deliveryId) ? $deliveryId : [$deliveryId];
        $r = [];
        foreach($deliveryId as $v) {
            $r[] = ['ome_delivery_id' => $v];
        }
        return $r;
    }
}
