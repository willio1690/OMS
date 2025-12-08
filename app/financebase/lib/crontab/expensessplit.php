<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/11/24 14:20:35
 * @describe: 费用拆分
 * ============================
 */
class financebase_crontab_expensessplit extends financebase_abstract_crontab
{
    public $_interval_time = 58; // 间隔时间

    /**
     * __construct
     * @return mixed 返回值
     */

    public function __construct()
    {
        $this->_time_key = sprintf("%s_time",__CLASS__);
        parent::__construct();
    }

    /**
     * 处理
     * @return mixed 返回值
     */
    public function process()
    {
        if(!app::get('financebase')->model('bill')->db_dump(array('split_status'=>'0'), 'id')) {
            return true;
        }
        $queueData = array();
        $queueData['queue_mode'] = 'expensessplit';
        $queueData['create_time'] = $this->now_time;
        $queueData['queue_name'] = "费用拆分".$this->now_time;
        $queueData['queue_data'] = array();
        $queue_id = $this->oQueue->insert($queueData);
        $queue_id and financebase_func::addTaskQueue(array('queue_id'=>$queue_id),'expensessplit');
        return true;
    }
}