<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
* 一条对接
*/
class ome_event_trigger_shop_data_delivery_yitiao extends ome_event_trigger_shop_data_delivery_common
{
    public function get_sdf($delivery_id)
    {
        $this->__sdf = parent::get_sdf($delivery_id);
        if ($this->__sdf) {
           
            $this->_get_order_objects_sdf($delivery_id);
            
        }
        return $this->__sdf;
    }
}