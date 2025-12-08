<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

trait o2o_event_trigger_logistics_data_electron_traitcommon {

    public function preDealDelivery($arrDeliveryId) {

        $arrDelivery = app::get('wap')->model('delivery')->getList('*', array('delivery_id'=>$arrDeliveryId));
        $billObj = app::get('wap')->model('delivery_bill');
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
            $deliveryItems[$delivery_id] = app::get('wap')->model('delivery_items')->getList('*', array('delivery_id' => $delivery_id),0,2);
        }
        return $deliveryItems[$delivery_id];
    }

    public function getDeliveryIdByWms($deliveryId){
        $deliveryId = $deliveryId ?: [0];

        $db = kernel::database();
        $sql = "SELECT w.outer_delivery_bn FROM sdb_wap_delivery as w WHERE w.delivery_id in (".implode(',', (array)$deliveryId).")";
        $delivery_list = $db->select($sql);
        $delivery_bnList = array();
        foreach ($delivery_list as $delivery){
            $delivery_bnList[] = $delivery['outer_delivery_bn'];
        }

        $deliveryArr = $db->select("SELECT d.delivery_id as ome_delivery_id FROM sdb_ome_delivery as d WHERE d.delivery_bn in(".'\''.implode('\',\'',$delivery_bnList).'\''.")");
        return $deliveryArr;
    }
}
