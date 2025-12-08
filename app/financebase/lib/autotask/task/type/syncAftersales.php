<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 同步售后单
 *
 * @author 334395174@qq.com
 * @version 0.1
 */
class financebase_autotask_task_type_syncAftersales extends financebase_autotask_task_init
{

    /**
     * 处理
     * @param mixed $task_info task_info
     * @param mixed $error_msg error_msg
     * @return mixed 返回值
     */

    public function process($task_info,&$error_msg)
    {

        //判断是否安装不安装直接返回成功
        if(app::get('finance')->is_installed()){
            $res1 = kernel::single('finance_cronjob_tradeScript')->get_aftersales();
            $res2 = kernel::single('finance_cronjob_tradeScript')->get_compensate();

            // 根据AR表的更新时间查找对应的账期，更新账期金额
            if (is_array($res1[2]) && $res1[2]['aftersales_time_start'] && $res1[2]['aftersales_time_end']) {

                $monthly_id = app::get('finance')->model('monthly_report_items')->getList('distinct monthly_id', [
                    'filter_sql' => 'up_time >= "'.date('Y-m-d H:i:s', $res1[2]['aftersales_time_start']).'" and up_time <= "'.date('Y-m-d H:i:s', $res1[2]['aftersales_time_end']).'"'
                ]);

                if ($monthly_id) {
                    $monthly_id = array_column($monthly_id, 'monthly_id');

                    finance_monthly_report::updateMonthlyAmount([
                        'monthly_id|in'=>$monthly_id
                    ]);
                }
            }

            if (is_array($res2[2]) && $res2[2]['compensate_time_start'] && $res2[2]['compensate_time_end']) {
                $monthly_id = app::get('finance')->model('monthly_report_items')->getList('distinct monthly_id', [
                    'filter_sql' => 'up_time >= "'.date('Y-m-d H:i:s', $res2[2]['compensate_time_start']).'" and up_time <= "'.date('Y-m-d H:i:s', $res2[2]['compensate_time_end']).'"'
                ]);

                if ($monthly_id) {
                    $monthly_id = array_column($monthly_id, 'monthly_id');

                    finance_monthly_report::updateMonthlyAmount([
                        'monthly_id|in'=>$monthly_id
                    ]);
                }
            }
        }
        
        return true;
    }

}