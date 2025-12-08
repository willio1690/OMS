<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_event_trigger_logistics_data_electron_360buy extends wms_event_trigger_logistics_data_electron_360buy {
    use ome_event_trigger_logistics_data_electron_traitcommon;

    public function getDeliverySdf($delivery, $shop) {
        $shopMdl          = app::get('ome')->model('shop');
        $shopInfo = $shopMdl->db_dump($delivery['shop_id']);
        if($shopInfo['shop_type'] != '360buy'
            && kernel::single('ome_branch')->getNodetypBybranchId($delivery['branch_id']) != 'selfwms') {
            return [];
        }
        $dOrder = $this->getDeliveryOrder($delivery['delivery_id']);
        $orderId = $sameRequest = array();
        $srKey = 0;
        $tmpOrderBn = '';
        foreach($dOrder as $val) {
            $tmpOrderBn .= $val['order_bn'] . ',';
            if(isset($tmpOrderBn[98])) {
                $tmpOrderBn = $val['order_bn'] . ',';
                $srKey++;
            }
            $sameRequest[$srKey]['order_bn'] =  $tmpOrderBn;
            $sameRequest[$srKey]['order_id'][] = $val['order_id'];
            $sameRequest[$srKey]['total_amount'] += $val['total_amount'];
            $orderId[] = $val['order_id'];
        }
        $orderExtendObj = app::get('ome')->model('order_extend');
        $orderExtends = $orderExtendObj->getList('order_id, receivable', array('order_id' => $orderId));
        $orderIdExtend = array();
        foreach($orderExtends as $extend) {
            $orderIdExtend[$extend['order_id']] = $extend;
        }
        $dlyCorp = app::get('ome')->model('dly_corp')->getList('*', array('corp_id'=>$delivery['logi_id']));
        $params = array();
        $params['delivery'] = $delivery;
        $params['shop'] = $shop;
        $params['dly_corp'] = $dlyCorp[0];
        $params['same_request'] = array();
        foreach($sameRequest as $key => $value) {
            if($key == 0) {
                $params['order_bn'] = trim($value['order_bn'], ',');
                $params['total_amount'] = $value['total_amount'];
                if ($delivery['is_cod'] == 'true') {
                    $params['receivable_amount'] = $this->getPayMoney($value['order_id'], $orderIdExtend);//代收货款金额
                }
            } else {
                if ($delivery['is_cod'] == 'true') {
                    $params['same_request'][] = array(
                        'order_bn' => trim($value['order_bn'], ','),
                        'total_amount' => $value['total_amount'],
                        'receivable_amount' => $this->getPayMoney($value['order_id'], $orderIdExtend)
                    );
                } else {
                    $params['same_request'][] = array(
                        'order_bn' => trim($value['order_bn'], ','),
                        'total_amount' => $value['total_amount'],
                    );
                }
            }
        }
        return $params;
    }

    private function getPayMoney($orderIds, $orderIdExtend) {
        $money = 0;
        foreach ($orderIds as $orderId) {
            $money += $orderIdExtend[$orderId]['receivable'];
        }
        return $money;
    }
}