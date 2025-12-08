<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class financebase_crontab_syncaftersales extends financebase_abstract_crontab
{
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
		$queueData = array();
                $queueData['queue_mode'] = 'syncAftersales';
                $queueData['create_time'] = $this->now_time;
                $queueData['queue_name'] = "同步应退单";
                $queueData['queue_data'] = array();
                $queue_id = $this->oQueue->insert($queueData);
                $queue_id and financebase_func::addTaskQueue(array('queue_id'=>$queue_id),'syncaftersales');
                return true;
	}
}