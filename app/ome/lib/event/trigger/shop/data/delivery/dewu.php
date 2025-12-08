<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * [德物平台]获取数据
 * 
 * @author wangbiao@shopex.cn
 * @version $Id: Z
 */
class ome_event_trigger_shop_data_delivery_dewu extends ome_event_trigger_shop_data_delivery_common
{
    public function get_sdf($delivery_id)
    {
        $this->__sdf = parent::get_sdf($delivery_id);

        if ($this->__sdf) {
            // $delivery = $this->__deliverys[$delivery_id];
            $order    = $this->__delivery_orders[$delivery_id];

            $this->__sdf['orderinfo']['shop_type'] = $order['shop_type'];
        }

        return $this->__sdf;
    }
}