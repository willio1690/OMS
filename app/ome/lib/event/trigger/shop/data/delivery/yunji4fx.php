<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0 
 * @DateTime: 2021/2/6 10:36:26
 * @describe: 类
 * ============================
 */

class ome_event_trigger_shop_data_delivery_yunji4fx extends ome_event_trigger_shop_data_delivery_common
{
    public function get_sdf($delivery_id)
    {
        $this->__sdf = parent::get_sdf($delivery_id);
        $delivery = $this->__deliverys[$delivery_id];
        $this->_get_delivery_items_sdf($delivery_id);

        return $this->__sdf;
    }

}