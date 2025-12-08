<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * Created by PhpStorm.
 * User: gehuachun
 * Date: 2018/11/20
 * Time: 11:18 AM
 */

class ome_event_trigger_shop_data_delivery_weimobr extends ome_event_trigger_shop_data_delivery_common
{
    /**
     * @param Int $delivery_id
     * @return array|void
     */
    public function get_sdf($delivery_id)
    {
        $this->__sdf = parent::get_sdf($delivery_id);
        if ($this->__sdf) {
            $this->__sdf['split_model'] = $this->_is_split_switch($delivery_id);
            $this->_get_order_objects_sdf($delivery_id);
            $this->_get_delivery_items_sdf($delivery_id);
            $this->_get_split_sdf($delivery_id);
        }
        return $this->__sdf;
    }
}