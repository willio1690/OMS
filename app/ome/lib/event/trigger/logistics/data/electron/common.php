<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2016-01-25
 * @describe 电子面单请求数据处理公用类
 */

class ome_event_trigger_logistics_data_electron_common
{
    public $channel;
    protected $directRet             = array();
    protected $needRequestId         = array();
    protected $needRequestDeliveryId = array();
    protected $needGetWBExtend       = false;

    public function setChannel($channel)
    {
        $this->channel = $channel;
        return $this;
    }

    public function getDeliverySdf($delivery, $shop)
    {
        return array();
    }

    protected function getDeliveryOrder($deliveryId)
    {
        $db     = kernel::database();
        $where  = is_array($deliveryId) ? 'd.delivery_id in (' . (implode(',', $deliveryId)) . ')' : 'd.delivery_id = "' . $deliveryId . '"';
        $field  = 'o.order_id, o.order_bn, o.total_amount, o.shop_id, o.createway, d.delivery_id';
        $sql    = 'select ' . $field . ' from sdb_ome_delivery_order as d left join sdb_ome_orders as o using(order_id) where ' . $where;
        $result = $db->select($sql);
        return $result;
    }

    //获取两条订单明细
    protected function getOrderItems($order_id)
    {
        static $orderItems = array();
        if (!$orderItems[$order_id]) {
            $orderItems[$order_id] = app::get('ome')->model('order_items')->getList('*', array('order_id' => $order_id), 0, 100);
        }
        return $orderItems[$order_id];
    }

    //获取两条发货单明细
    public function getDeliveryItems($delivery_id)
    {
        static $deliveryItems = array();
        if (!$deliveryItems[$delivery_id]) {
            $deliveryItems[$delivery_id] = app::get('ome')->model('delivery_items')->getList('*', array('delivery_id' => $delivery_id), 0, 100);
        }
        return $deliveryItems[$delivery_id];
    }

    public function getDirectSdf($delivery, $corp)
    {
        $items = app::get('ome')->model('delivery_items')->getList('*', array('delivery_id' => $delivery['delivery_id']));

        // 发货地址
        $shop = app::get('logisticsmanager')->model('channel_extend')->dump(array('channel_id' => $this->channel['channel_id']), 'shop_name,province,city,area,address_detail,seller_id,default_sender,mobile,tel,zip,addon');
        if ($shop['addon']['use_branch_addr']) {
            $branch = app::get('ome')->model('branch')->db_dump($delivery['branch_id']);

            list(, $mainland)             = explode(':', $branch['area']);
            list($province, $city, $area) = explode('/', $mainland);

            $shop['shop_name']      = $branch['name'];
            $shop['province']       = $province;
            $shop['city']           = $city;
            $shop['area']           = $area;
            $shop['address_detail'] = $branch['address'];
            $shop['default_sender'] = $branch['uname'];
            $shop['mobile']         = $branch['mobile'];
            $shop['tel']            = $branch['phone'];
            $shop['zip']            = $branch['zip'];

        }

        $delivery_orders = app::get('ome')->model('delivery_order')->getList('*', array('delivery_id' => $delivery['delivery_id']));

        $order_id = array_column($delivery_orders, null, 'order_id');

        $orders = app::get('ome')->model('orders')->getList('total_amount', array('order_id' => $order_id));

        $total_amount = 0;
        foreach ($orders as $order) {
            $total_amount += $order['total_amount'];
        }

        $sdf                  = array();
        $sdf['primary_bn']    = $delivery['delivery_bn'];
        $sdf['delivery']      = $delivery;
        $sdf['delivery_item'] = $items;
        $sdf['shop']          = $shop;
        $sdf['dly_corp']      = $corp;
        $sdf['total_amount']  = $total_amount;

        return $sdf;
    }

    #设置子单的请求的订单号
    public function setChildRqOrdNo($deliveryBn, $billId){
        $deliveryBn = $deliveryBn."cd".$billId;
        return $deliveryBn;
    }

}
