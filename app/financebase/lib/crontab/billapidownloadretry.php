<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class financebase_crontab_billapidownloadretry extends financebase_abstract_crontab
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
        // 单线程执行
        base_kvstore::instance('financebase')->fetch(__CLASS__,$isRunning);
        if ($isRunning == 1) {
            return ;
        }

        base_kvstore::instance('financebase')->store(__CLASS__,1, 3600);

        $mdl = app::get('financebase')->model('queue');
        $queueList = $mdl->getList('queue_id', [
            'queue_mode'    => 'billApiDownload',
            'is_file_ready' => '0',
            'status'        => 'error',
        ]);

        foreach ($queueList as $queue) {
            $affect_rows = $mdl->update([
                'status' => 'ready',
            ],[
                'queue_id' => $queue['queue_id'],
                'status' => 'error',
                'is_file_ready' => '0',
            ]);

            if ($affect_rows === 1) {
                kernel::single('financebase_autotask_task_process')->process([
                    'queue_id' => $queue['queue_id'],
                ],$msg);
            }
        }

        base_kvstore::instance('financebase')->delete(__CLASS__);

        return ;
    }

    /**
     * 设置Time
     * @return mixed 返回操作结果
     */
    public function setTime()
    {
        // 加5分钟
        $next_run_time = strtotime('+5 minutes');

        $this->financeObj->store($this->_time_key, $next_run_time);

        return true;
    }
}