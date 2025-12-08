<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 定时任务处理类
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

class ome_autotask_timer_misctask
{
    public function process($params, &$error_msg=''){
        kernel::single('base_misc_autotask')->trigger();

        return true;
    }
}