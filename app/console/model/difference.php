<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2022/11/21 11:47:12
 * @describe: model层
 * ============================
 */
class console_mdl_difference extends dbeav_model {

    /**
     * gen_id
     * @return mixed 返回值
     */

    public function gen_id() {
        $prefix = 'DF'.date("ymd");
        $sign   = kernel::single('eccommon_guid')->incId('console_mdl_difference', $prefix, 6);
        return $sign;
    }
}