<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class billcenter_autotask_timer_sales
{
    /**
     * 处理
     * @param mixed $params 参数
     * @param mixed $error_msg error_msg
     * @return mixed 返回值
     */
    public function process($params, &$error_msg=''){
        set_time_limit(0); ignore_user_abort(1);
        
        if (!app::get('finance')->is_installed()) {
            $error_msg = 'JIT转AR失败：APP:finance未安装';
            return true;
        }
        
        // 判断有没有开启账期
        $init_time = app::get('finance')->getConf('finance_setting_init_time');
        if (!$init_time) {
            $error_msg = 'JIT转AR失败：未配置账期';
            return true;
        }
        
        $execTime = 600;
        
        $startTime = microtime(true);
        
        // 查询销售单
        do {
            $endTime = microtime(true);
            if (($startTime - $endTime) >= $execTime) {
                break;
            }
            
            $sale = app::get('billcenter')->model('sales')->db_dump(['in_ar' => '0']);
            
            if (!$sale) {
                break;
            }
            
            $res = kernel::single('billcenter_sales')->transferAr($sale['id']);
            
        } while (true);
        
        return true;
    }
    
    
}