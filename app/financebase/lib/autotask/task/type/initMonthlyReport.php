<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 生成新账期任务
 *
 * @author 334395174@qq.com
 * @version 0.1
 */
class financebase_autotask_task_type_initMonthlyReport extends financebase_autotask_task_init
{

    /**
     * 处理
     * @param mixed $task_info task_info
     * @param mixed $error_msg error_msg
     * @return mixed 返回值
     */

    public function process($task_info,&$error_msg)
    {

        $begin_time = $task_info['queue_data']['begin_time'];
        $end_time = $task_info['queue_data']['end_time'];

        if(!$begin_time || !$end_time){
            $error_msg[] = '没有时间范围';
            return true;
        }

        // 只生成 淘宝、京东的账单
        $mdlShop = app::get('ome')->model('shop');
        // $mdlShopExtends = app::get('ome')->model('shop_extends');
        $mdlMonthlyReport = app::get('finance')->model('monthly_report');
        $mdlBill = app::get('finance')->model('bill');
        $mdlAr = app::get('finance')->model('ar');

        $shop_list = array();
        $tmp_shop_list = financebase_func::getShopList(financebase_func::getShopType());

        if($tmp_shop_list)
        {
            $shop_list = array_column($tmp_shop_list,'shop_id');
        }

        // 处理店铺
        if($shop_list)
        {
            $monthly_report_list = $mdlMonthlyReport->getListByTime($begin_time);
            $monthly_report_list and $monthly_report_list = array_column($monthly_report_list,'shop_id');

            foreach ($shop_list as $shop_id) 
            {
                if(in_array($shop_id, $monthly_report_list)) continue;
                $data = array();
                $data['monthly_date'] = $task_info['queue_data']['monthly_date'];
                $data['begin_time']     = $begin_time;
                $data['end_time']       = $end_time;
                $data['shop_id']        = $shop_id;

                $mdlMonthlyReport->insert($data);
            }
        }


        return true;

    }
}