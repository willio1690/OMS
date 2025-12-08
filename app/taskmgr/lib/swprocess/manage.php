<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

use Swoole\Process\Manager;
use Swoole\Process\Pool;
use Swoole\Process;
use Swoole\Coroutine;

class taskmgr_swprocess_manage
{
    private static $pid_file = '/var/run/swoole-taskmgr.pid';

    /**
     * 获取已运行队列manage进程pid
     */
    public function getPid()
    {
        if (file_exists(self::$pid_file)) {
            $pid = file_get_contents(self::$pid_file);
            if ($pid && Process::kill($pid, 0)) {
                return $pid;
            }
        }
        return false;
    }

    /**
     * 运行
     * @throws \Exception
     */
    public function run()
    {
        //写入pid
        if ($this->getPid()) {
            taskmgr_log::error('已启动不可重复启动', [], 'system');
            exit();
        }

        file_put_contents(self::$pid_file, getmypid());

        $pm = new Manager();

        // 消费任务
        foreach (taskmgr_whitelist::get_all_task_list() as $taskName => $taskConf) {
            $workerNum = $taskConf['threadNum'] ?: 1;

            $pm->addBatch($workerNum, function (Pool $pool, int $workerId)use($taskName, $taskConf) {
                $process = $pool->getProcess($workerId);
                $process->name('taskmgr-'.$taskName);

                list($result, $msg) = (new taskmgr_swtask_worker())->run($taskName, $taskConf);

                if (!$result) {
                    taskmgr_log::error(sprintf('%s(pid:%s,wid:%s)', $msg?:$taskName.'-任务未返回结果',getmypid(), $workerId), [], 'system');

                    $pool->shutdown();
                }
            },true);
        }

        // 订阅任务
        foreach (taskmgr_whitelist::init_list() as $taskName => $taskConf) {
            // 检查规则
            if (!taskmgr_swtask_parsecrontab::check($taskConf['rule'])) {
                taskmgr_log::error(sprintf('[%s]CRONTAB规则定义错误', $taskName), [], 'system');

                exit();
            }

            $pm->addBatch(1, function (Pool $pool, int $workerId)use($taskName, $taskConf) {
                $process = $pool->getProcess($workerId);
                $process->name('taskmgr-'.$taskName);


                // 每秒执行一次
                $timerId = Swoole\Timer::tick(1000, function(int $timer_id, $pool, $workerId, $taskName, $taskConf){
                    
                    list($result, $msg) = (new taskmgr_swtask_timer())->run($taskName, $taskConf);

                    if (!$result) {
                        taskmgr_log::error(sprintf('%s(pid:%s,wid:%s)', $msg?:$taskName.'-任务未返回结果',getmypid(), $workerId), [], 'system');
                        
                        $pool->shutdown();
                    }
                    
                }, $pool, $workerId, $taskName, $taskConf);

                $process->signal(SIGTERM, function ($sig) use ($process, $timerId) {

                    if ($timerId !== null) {
                        Swoole\Timer::clear($timerId);
                    }

                });
                
            },true);
        }

        // 日志清理
       $this->cleanupLogs($pm);

        $pm->start();
    }

    /**
     * 使用swoole manager协程清理日志，每10秒执行一次
     */
    public function cleanupLogs($pm)
    {
        $pm->addBatch(1, function (Pool $pool, int $workerId) {

            $process = $pool->getProcess($workerId);
            $process->name('taskmgr-cleanupLogs');

            // 使用 Swoole\Timer::tick 替代 process->tick
            $timerId = Swoole\Timer::tick(10000, function (int $timer_id)  {
                try {
                    $threeDaysAgo = date('Ymd', strtotime('-3 days'));
                    $logDir = taskmgr_log::getDirPath($threeDaysAgo);
                    taskmgr_log::info('日志目录: ' . $logDir, [], 'system');
                    if ($logDir && is_dir($logDir)) {
                        // 在这里添加实际的日志清理逻辑
                        $files = glob($logDir . '/*.log');
                        foreach ($files as $file) {
                            if (file_exists($file)) {
                                unlink($file);
                            }
                        }
                        
                        if (is_dir($logDir)) {
                            rmdir($logDir);
                            taskmgr_log::info('日志清理完成: ' . $logDir, [], 'system');
                        }
                    }
                } catch (\Throwable $e) {
                    taskmgr_log::error('日志清理异常: ' . $e->getMessage(), [], 'system');
                }
            });

            // 处理进程终止信号
            $process->signal(SIGTERM, function ($sig) use ($process, $timerId) {
                if ($timerId !== null) {
                    Swoole\Timer::clear($timerId);
                }

                taskmgr_log::info('日志清理进程正在停止...', [], 'system');
            });

        }, true);
    }
}
