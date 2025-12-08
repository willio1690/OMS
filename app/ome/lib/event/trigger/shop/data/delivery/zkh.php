<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 获取数据
 * Class ome_event_trigger_shop_data_delivery_zkh
 */
class ome_event_trigger_shop_data_delivery_zkh extends ome_event_trigger_shop_data_delivery_common
{
    public function get_sdf($delivery_id)
    {
        $this->__sdf = parent::get_sdf($delivery_id);
        if ($this->__sdf) {
            $this->_get_delivery_items_sdf($delivery_id);
//            $this->_get_order_objects_sdf($delivery_id);
            $this->_get_split_sdf($delivery_id);
            $this->__sdf['branch'] = $this->_get_branch($this->__deliverys[$delivery_id]['branch_id']);
    
            $order = $this->__sdf['orderinfo'];
            //获取所有包裹
            $orderDelivery = app::get('ome')->model('delivery')->getAllDeliversOrderId($order['order_id']);
            $delivery_package = [];
            foreach($orderDelivery as $value){
                $package = $this->_get_delivery_package($value['delivery_id']);
                $delivery_package    = array_merge($delivery_package,(array)$package);
            }
            $this->__sdf['delivery_package'] = $delivery_package;
        }
        
        return $this->__sdf;
    }
}
