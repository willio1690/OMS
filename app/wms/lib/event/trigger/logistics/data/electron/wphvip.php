<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_event_trigger_logistics_data_electron_wphvip extends wms_event_trigger_logistics_data_electron_common
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
        $deliveryExtend = array();

        foreach ($arrDelivery as $delivery) {
            $package_items = $this->getpackage_items($delivery['delivery_id']);
            $failArray     = array('delivery_id' => $delivery['delivery_id'], 'delivery_bn' => $delivery['delivery_bn']);
            if (empty($delivery['ship_province']) || empty($delivery['ship_addr'])) {
                $failArray['msg']          = '收货地址省份和详细地址不能少';
                $this->directRet['fail'][] = $failArray;
                continue;
            }
            if (empty($package_items)) {
                $failArray['msg']          = '包裹明细不能少';
                $this->directRet['fail'][] = $failArray;
                continue;
            }
            if (strlen($delivery['ship_district']) > 60) {
                $failArray['msg']          = '地区长度最多20个汉字';
                $this->directRet['fail'][] = $failArray;
                continue;
            }
            $delivery['ship_mobile'] = trim($delivery['ship_mobile']);
            $delivery['ship_tel']    = trim($delivery['ship_tel']);
            if (strlen($delivery['ship_mobile']) > 20 && !in_array($delivery['shop_type'], array('360buy', 'pinduoduo', 'vop'))) {
                $failArray['msg']          = '手机号码超出长度';
                $this->directRet['fail'][] = $failArray;
                continue;
            }
            if (strlen($delivery['ship_tel']) > 20 && !in_array($delivery['shop_type'], array('360buy', 'pinduoduo', 'vop'))) {
                $failArray['msg']          = '座机号超出长度';
                $this->directRet['fail'][] = $failArray;
                continue;
            }
            if (empty($delivery['ship_mobile']) && empty($delivery['ship_tel'])) {
                $failArray['msg']          = '收货地址手机号和电话不能同时少';
                $this->directRet['fail'][] = $failArray;
                continue;
            }
            $orderBn = array();
            foreach ($delivery['delivery_order'] as $val) {
                if ($val['shop_type'] == 'pinduoduo' && $val['createway'] == 'matrix') {
                    $orderBn[] = $val['order_bn'];
                }
            }
            $tmpDelivery                        = $delivery;
            $tmpDelivery['order_channels_type'] = $orderBn ? 'PDD' : 'OTHER';
            $tmpDelivery['package_items']       = $package_items;
            $tmpDelivery['order_bn']            = $orderBn;
            $deliveryExtend[]                   = $tmpDelivery;
            if (empty($arrBill)) {
                $this->needRequestId[] = $delivery['delivery_id'];
            } else {
                $dlyExtend      = $deliveryExtend[0];
                $deliveryExtend = array();
                foreach ($arrBill as $bill) {
                    $tmp                   = $dlyExtend;
                    $this->needRequestId[] = $bill['b_id'];
                    $tmp['delivery_bn']    = $this->setChildRqOrdNo($dlyExtend['delivery_bn'], $bill['b_id']);
                    $deliveryExtend[]      = $tmp;
                }
                break;
            }
        }
        if (empty($deliveryExtend)) {
            return false;
        }
        $primary_bn = uniqid('oep');

        $sdf               = parent::getDirectSdf($arrDelivery, $arrBill, $shop);
        $sdf['primary_bn'] = $primary_bn;
        $sdf['shop']       = $shop;
        $sdf['delivery']   = $deliveryExtend;

        return $sdf;
    }

    # 获取包裹明细
    /**
     * 获取package_items
     * @param mixed $delivery_id ID
     * @return mixed 返回结果
     */
    public function getpackage_items($delivery_id)
    {
        $items   = $this->getDeliveryItems($delivery_id);

        $package = array();
        foreach ($items as $item) {
            $product_name = $item['product_name'] ? $item['product_name'] : '商品名称';
            if (isset($product_name[120])) {
                $product_name = mb_substr($product_name, 0, 120, 'utf8');
            }
            $package[] = array('item_name' => $product_name, 'count' => $item['number'],'bn'=>$item['bn']);
        }
        return $package;
    }
}