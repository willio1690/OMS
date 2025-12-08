<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Datetime: 2022/3/3
 */

class ome_event_trigger_shop_data_delivery_yunji4pop extends ome_event_trigger_shop_data_delivery_common
{
    public function get_sdf($delivery_id)
    {
        $this->__sdf = parent::get_sdf($delivery_id);
        if ($this->__sdf) {
            $this->_get_delivery_items_sdf($delivery_id);
        }
        return $this->__sdf;
    }
    
}