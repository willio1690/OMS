<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2022/12/6 10:16:36
 * @describe: 日盘
 * ============================
 */
class console_mdl_dailyinventory extends dbeav_model
{
    /**
     * gen_id
     * @param mixed $channel_bn channel_bn
     * @return mixed 返回值
     */

    public function gen_id($channel_bn)
    {
        $prefix = $channel_bn . '-INVS' . date("Ymd");
        $guid   = kernel::single('eccommon_guid')->incId($channel_bn . '-INVS', $prefix, 2, true);

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
