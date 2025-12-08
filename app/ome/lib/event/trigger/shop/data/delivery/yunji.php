<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_event_trigger_shop_data_delivery_yunji extends ome_event_trigger_shop_data_delivery_common
{
    public function get_sdf($delivery_id)
    {
        $this->__sdf = parent::get_sdf($delivery_id);
        if ($this->__sdf) {
            $this->__sdf['split_model'] = $this->_is_split_switch($delivery_id);
            $this->_get_delivery_items_sdf($delivery_id);
            $this->_get_split_sdf($delivery_id);
            $order_items = $this->_get_order_objects($delivery_id);
            $this->__sdf['order_items'] = $order_items;
        }
        return $this->__sdf;
    }
    
}