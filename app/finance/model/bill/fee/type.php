<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_mdl_bill_fee_type extends dbeav_model
{
    /**
     * modifier_channel
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function modifier_channel($row)
    {
        return finance_channel::$channel_name[$row];
    }
}