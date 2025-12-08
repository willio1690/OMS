<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
class o2o_autotask_timer_storedaliy
{
    /**
     * 处理
     * @param mixed $params 参数
     * @param mixed $error_msg error_msg
     * @return mixed 返回值
     */
    public function process($params, &$error_msg=''){
        set_time_limit(0);
        ignore_user_abort(1);

        kernel::single('o2o_analysis_store_daliy')->generate();
        
        //每日(03点)统计补货差异单数据
        kernel::single('o2o_analysis_store_daliy')->statisReplenish();
        
        return true;
    }
}