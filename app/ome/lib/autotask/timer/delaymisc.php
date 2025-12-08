<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0 
 * @DateTime: 2020/11/3 11:39:18
 * @describe: 延时定时任务
 * ============================
 */

class ome_autotask_timer_delaymisc
{
    public function process($params, &$error_msg=''){
        set_time_limit(0);
        ignore_user_abort(1);

        app::get('ome')->model('misc_task')->process();

        return true;
    }
}