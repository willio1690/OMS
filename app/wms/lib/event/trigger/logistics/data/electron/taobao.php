<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_event_trigger_logistics_data_electron_taobao extends wms_event_trigger_logistics_data_electron_common {

    

    /**
     * 获取DirectSdf
     * @param mixed $arrDelivery arrDelivery
     * @param mixed $arrBill arrBill
     * @param mixed $shop shop
     * @return mixed 返回结果
     */
    public function getDirectSdf($arrDelivery, $arrBill, $shop) {
        $deliveryExtend = array();
        $deliveryOrder = $this->getDeliveryOrder($this->needRequestDeliveryId);
        $dlyIdOrder = array();
        foreach($deliveryOrder as $val) {
            $dlyIdOrder[$val['wms_delivery_id']][] = $val;
        }
        foreach($arrDelivery as $delivery) {
            $order = $dlyIdOrder[$delivery['delivery_id']];
            $order_channels_type = $this->getOrderChannelsType($order);
            $item_name = $this->getItemName($order);
            $package_items = $this->getpackage_items($delivery['delivery_id']);
            if(empty($delivery['ship_province']) || empty($delivery['ship_addr'])){
                $this->directRet['fail'][] =  array('delivery_id' => $delivery['delivery_id'], 'msg'=>'收货地址省份和详细地址不能少');
                continue;
            }
            if(empty($package_items)){
                $this->directRet['fail'][] =  array('delivery_id' => $delivery['delivery_id'], 'msg'=>'包裹明细不能少');
                continue;
            }
            if(strlen($delivery['ship_district'])>60){
                $this->directRet['fail'][] =  array('delivery_id' => $delivery['delivery_id'], 'msg'=>'地区长度最多20个汉字');
                continue;
            }
            $delivery['ship_mobile'] = trim($delivery['ship_mobile']);
            $delivery['ship_tel'] = trim($delivery['ship_tel']);
            /*$consigneePhone = $delivery['ship_mobile'] != '' ? $delivery['ship_mobile'] : $delivery['ship_tel'];
            if( strlen($consigneePhone)>20 && !in_array($delivery['shop_type'], array ('360buy','pinduoduo','vop')) ){
                $this->directRet['fail'][] =  array('delivery_id' => $delivery['delivery_id'], 'msg'=>'电话超出长度');
                continue;
            }*/
            $orderBn = $shopexB2bOrder = $tbfxOrder = array();
            foreach($order as $val) {
                if($val['createway'] == 'matrix') {
                    $frontShop = $this->getShop($val['shop_id']);
                    if($frontShop['node_type'] == 'shopex_b2b') {
                        $shopexB2bOrder[$val['order_id']] = $val;
                    } elseif ($frontShop['node_type'] == 'taobao' && $frontShop['business_type'] == 'fx') {
                        $tbfxOrder[$val['order_id']] = $val;
                    } else {
                        $orderBn[] = $val['order_bn'];
                    }
                }
            }
            if($shopexB2bOrder) {
                $shopexBn = $this->_getShopexBn($shopexB2bOrder);
                is_array($shopexBn) && $orderBn = array_merge($orderBn, $shopexBn);
            }
            if($tbfxOrder) {
                $tbfxBn = $this->_getTbfxBn($tbfxOrder);
                is_array($tbfxBn) && $orderBn = array_merge($orderBn, $tbfxBn);
            }
            $tmpDelivery = $delivery;
            $tmpDelivery['order_channels_type'] = $order_channels_type;

            //取描述
            
            $goods_description = $this->getGoodsDesc($delivery['delivery_id']);

            if($goods_description) $tmpDelivery['goods_description'] = $goods_description;
            $tmpDelivery['item_name'] = $item_name;
            $tmpDelivery['package_items'] = $package_items;
            $tmpDelivery['order_bn'] = $orderBn;
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
        if(empty($deliveryExtend)) {
            return false;
        }
        $primary_bn = uniqid('oet');
        $sdf = parent::getDirectSdf($arrDelivery, $arrBill, $shop);
        $sdf['primary_bn'] = $primary_bn;
        $sdf['shop']       = $shop;
        $sdf['delivery']   = $deliveryExtend;
        return $sdf;
    }

    #获取订单店铺类型
    /**
     * 获取OrderChannelsType
     * @param mixed $order order
     * @return mixed 返回结果
     */
    public function getOrderChannelsType($order) {
        $tbbusiness_type = 'other';
        $createway = 'local';
        $node_type = '';
        foreach ($order as $k => $v) {
            $createway = $v['createway'];
            if ($v['createway'] != 'matrix') {
                break;
            }
        }

        if ($order && $createway == 'matrix') {
            $shop_id = $order[0]['shop_id'];
            $shop = $this->getShop($shop_id);
            if ($shop) {
                $tbbusiness_type = $shop['tbbusiness_type'];
                $node_type       = $shop['node_type'];
            }
        }
        $order_channels_type = logisticsmanager_waybill_taobao::get_order_channels_type($tbbusiness_type, $node_type);
        return $order_channels_type;
    }

    # 获取商品名称
    /**
     * 获取ItemName
     * @param mixed $order order
     * @return mixed 返回结果
     */
    public function getItemName($order) {
        $item_name = 'other item name';
        if ($order) {
            $firstOrderId = $order[0]['order_id'];
            $orderItems = $this->getOrderItems($firstOrderId);
            if ($orderItems) {
                //订单明细中第一个商品名称
                $item_name = $orderItems[0]['name'] ? $orderItems[0]['name']:'other item name';
                if(isset($item_name[120])) {
                    $item_name = mb_substr($item_name, 0, 120, 'utf8');
                }
            }
        }
        return $item_name;
    }

    # 获取包裹明细
    /**
     * 获取package_items
     * @param mixed $delivery_id ID
     * @return mixed 返回结果
     */
    public function getpackage_items($delivery_id)
    {
        $items = $this->getDeliveryItems($delivery_id);
        $package = array();
        foreach ($items as $item ) {
            $product_name = $item['product_name'] ? $item['product_name'] : '商品名称';
            if(isset($product_name[120])) {
                $product_name = mb_substr($product_name, 0, 120, 'utf8');
            }
            $package[] = array('item_name'=>$product_name,'count'=>$item['number']);
        }
        return $package;
    }
    /**
     * 获得店铺信息
     * @param String $shop_id 店铺ID
     */
    public function getShop($shop_id) {
        static $shop = array();
        if (!$shop[$shop_id]) {
            $shop[$shop_id] = app::get('ome')->model('shop')->dump(array('shop_id' => $shop_id));
        }
        return $shop[$shop_id];
    }

    private function _getShopexBn($arrOrder) {
        $fxw = app::get('ome')->model('fxw_orders')->getList('*', array('order_id'=>array_keys($arrOrder)));
        $fxwData = array();
        foreach($fxw as $val) {
            if($val){
                $fxwData[$val['order_id']] = $val;
            }
            
        }
        $shopexBn = array();
        foreach($arrOrder as $order) {
            $shopexBn[] = $fxwData[$order['order_id']]['dealer_order_id'] ? $fxwData[$order['order_id']]['dealer_order_id'] : $order['order_bn'];
        }
        return $shopexBn;
    }

    private function _getTbfxBn($arrOrder) {
        $tbfx = app::get('ome')->model('tbfx_orders')->getList('*', array('order_id'=>array_keys($arrOrder)));
        $tbfxData = array();
        foreach($tbfx as $val) {
            if($val){
                $tbfxData[$val['order_id']] = $val;
            }
            
        }
        $tbfxBn = array();
        foreach ($arrOrder as $order) {
            $tbfxBn[] = $tbfxData[$order['order_id']]['tc_order_id'] ? $tbfxData[$order['order_id']]['tc_order_id'] : $order['order_bn'];
        }
        return $tbfxBn;
    }

    /**
     * 获取GoodsDesc
     * @param mixed $delivery_id ID
     * @return mixed 返回结果
     */
    public function getGoodsDesc($delivery_id){
        $items = $this->getDeliveryItems($delivery_id);
        $product_ids = array_column($items, 'product_id');

        $extObj = app::get('material')->model('basic_material_ext');
        $extList = $extObj->getlist('cat_id',array('bm_id'=>$product_ids));
        $cat_ids = array_map('current',$extList);
        $typeObj = app::get('ome')->model('goods_type');
        $typeList = $typeObj->getlist('name',array('type_id'=>$cat_ids));

        if($typeList){
            return $typeList[0]['name'];
        }
    }
}