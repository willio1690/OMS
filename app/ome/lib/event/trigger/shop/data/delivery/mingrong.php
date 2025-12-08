<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 
 * 名融
 * Date: 2019/2/20
 * sunjing
 */

class ome_event_trigger_shop_data_delivery_mingrong extends ome_event_trigger_shop_data_delivery_common
{
    public function get_sdf($delivery_id)
    {
        parent::get_sdf($delivery_id);
        $this->_get_split_sdf($delivery_id);
        $this->_get_delivery_items_sdf($delivery_id);
        return $this->__sdf;
    }
}