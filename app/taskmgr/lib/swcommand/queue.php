<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 队列执行命令
 *
 * @author chenping@shopex.cn
 * @version Fri May  6 23:15:31 2022
 */

class taskmgr_swcommand_queue extends taskmgr_swcommand_base
{
    private $table;

    protected static $signature = [
        'queue:count' => 'count',
        'queue:keys'  => 'keys',
    ];

    protected static $description = [
        'queue:count' => '获取队列长度，携带参数队列名称',
        'queue:keys'  => '显示队列名称',
    ];

    /**
     * 启动
     */
    public function count()
    {
        global $argv;

        if (!$argv[2]) {
            taskmgr_swconsole_output::error('请输入队列名' . PHP_EOL);

            exit();
        }

        $count = taskmgr_swprocess_queue::count($argv[2]);

        taskmgr_swconsole_output::info(sprintf("%s\t%s", $argv[2], $count) . PHP_EOL);
    }

    public function keys()
    {
        $queueNames = taskmgr_swprocess_queue::getKeys();

        taskmgr_swconsole_output::info(implode($queueNames, PHP_EOL) . PHP_EOL);
    }
}
