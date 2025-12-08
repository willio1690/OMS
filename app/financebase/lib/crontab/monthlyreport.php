<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class financebase_crontab_monthlyreport extends financebase_abstract_crontab
{
    /**
     * __construct
     * @return mixed 返回值
     */
    public function __construct()
    {
        $this->_time_key = sprintf("%s_time", __CLASS__);
        parent::__construct();
    }

    /**
     * 处理
     * @return mixed 返回值
     */
    public function process()
    {

        $init_time = app::get('finance')->getConf('finance_setting_init_time');

        if (!$init_time) {
            return false;
        }
        $incr = $init_time['cycle'] == "day" ? "+1 day" : "+1 month";
        $next_time = strtotime($incr);
        $next_time = strtotime("+1 day", $next_time);

        $report_info = app::get("finance")->model("monthly_report")->getList('end_time', array(), 0, 1, ' monthly_id desc');
        if (!$report_info) {
            $report_info[0]['end_time'] = strtotime($init_time['year'].'-'.$init_time['month'].'-'.$init_time['day']) - 1;
        }
        if($init_time['cycle'] == "day"){
            if (date('Y-m-d', $next_time) == date('Y-m-d', $report_info[0]['end_time'])) {
                return false;
            }
        } else {
            if (date('Y-m', $next_time) == date('Y-m', $report_info[0]['end_time'])) {
                return false;
            }
        }

        $begin_time = $report_info[0]['end_time'] + 1;

        $diff_time = 3600 * 12;

        $monthly_date = $init_time['cycle'] == "day" ? date('Y年m月d日账期', $begin_time) : date('Y年m月账期', $begin_time);
        if ($diff_time <= $next_time - $begin_time) {

            $end_time                 = strtotime($incr, $begin_time);
            $queueData                = array();
            $queueData['queue_mode']  = 'initMonthlyReport';
            $queueData['create_time'] = time();
            $queueData['queue_name']  = sprintf("账期初始化_%s", $monthly_date);
            $queueData['queue_data']  = array('begin_time' => $begin_time, 'end_time' => $end_time - 1, 'monthly_date' => $monthly_date);

            $queue_id = $this->oQueue->insert($queueData);
            $queue_id and financebase_func::addTaskQueue(array('queue_id' => $queue_id), 'initmonthlyreport');
        }

        return true;
    }

    /**
     * 设置Time
     * @return mixed 返回操作结果
     */
    public function setTime()
    {
        $next_run_time = strtotime(date("Y-m-d", strtotime("+1 day")) . " 23:00:00");

        $this->financeObj->store($this->_time_key, $next_run_time);
        return true;
    }

}
