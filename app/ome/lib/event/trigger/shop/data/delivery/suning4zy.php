<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * Created by PhpStorm.
 * User: gehuachun
 * Date: 2018-12-28
 * Time: 15:31
 */
class ome_event_trigger_shop_data_delivery_suning4zy extends ome_event_trigger_shop_data_delivery_common
{
    public function get_sdf($delivery_id)
    {
        $this->__sdf = parent::get_sdf($delivery_id);

        if ($this->__sdf) {
            $this->_get_order_all_objects_sdf($delivery_id);
        }

        return $this->__sdf;
    }
}