<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2015-12-16
 * @describe 发起电子面单请求 每次请求一条
 */
class brush_electron_360buy extends brush_electron_abstract{

    /**
     * deliveryToSdf
     * @param mixed $delivery delivery
     * @return mixed 返回值
     */

    public function deliveryToSdf($delivery) {
        $sdf = parent::deliveryToSdf($delivery);
        $sdf['primary_bn'] = $delivery[0]['delivery_bn'];
        $sdf['preNum'] = 1;
        $sdf['delivery'] = $delivery[0];
        return $sdf;
    }

    //回填发货信息
    /**
     * delivery
     * @return mixed 返回值
     */
    public function delivery() {
        $deliveryIds = array();
        foreach($this->delivery as $delivery) {
            $deliveryIds[] = $delivery['delivery_id'];
        }
        $this->getDeliveryOrder($deliveryIds);
        $shop = $this->getChannelExtend();
        $dlyCorp = $this->getDlyCorp($this->delivery[0]['logi_id']);
        foreach($this->delivery as $delivery) {
            $totalAmount = 0;
            $order = $this->getDeliveryOrder($delivery['delivery_id']);
            $orderBn = array();
            foreach($order['order_info'] as $val) {
                $orderBn[] = $val['order_bn'];
                $totalAmount += $val['total_amount'];
            }
            $params = array();
            $params['delivery'] = $delivery;
            $params['shop'] = $shop;
            $params['dly_corp'] = $dlyCorp;
            $params['total_amount'] = $totalAmount;
            $params['order_bn'] = implode(',', $orderBn);
            if ($delivery['is_cod'] == 'true') {
                $params['receivable_amount'] = $this->getPayMoney($order['order_id']);//代收货款金额
            }
            $this->request('delivery', $params);
        }
    }

    private function getPayMoney($orderIds) {
        $money = 0;
        $orderExtendObj = app::get('ome')->model('order_extend');
        $orderExtends = $orderExtendObj->getList('receivable', array('order_id' => $orderIds));
        foreach ($orderExtends as $extend) {
            $money += $extend['receivable'];
        }
        return $money;
    }
}