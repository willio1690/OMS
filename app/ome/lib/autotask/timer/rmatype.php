<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 售后类型分布报表任务处理类
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

class ome_autotask_timer_rmatype
{
    public function process($params, &$error_msg=''){
        set_time_limit(0);
        ignore_user_abort(1);

        kernel::single('omeanalysts_crontab_script_rmatype')->statistics();

        return true;
    }
}