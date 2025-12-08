<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class pos_mdl_inventory_snapshot extends dbeav_model
{
    /**
     * gen_id
     * @param mixed $store_bn store_bn
     * @return mixed 返回值
     */
    public function gen_id($store_bn)
    {
        $prefix = $store_bn . '-INVS' . date("Ymd");
        $guid   = kernel::single('eccommon_guid')->incId($store_bn . '-INVS', $prefix, 2, true);

        return $guid;
    }

    /**
     * modifier_stock_date
     * @param mixed $value value
     * @return mixed 返回值
     */
    public function modifier_stock_date($value)
    {
        return $value;
    }
}
