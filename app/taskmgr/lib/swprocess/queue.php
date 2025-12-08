<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

if (!defined('__CONNECTER_MODE')){
    require_once dirname(__FILE__) . '/../../shell/shell.php';
}

class taskmgr_swprocess_queue
{
    // private static $driver;

    /**
     *
     *
     * @return mixed
     * @author
     **/
    public static function getDriver($taskType)
    {
        $connecterClass = sprintf('taskmgr_connecter_%s', __CONNECTER_MODE);
        $driver   = new $connecterClass();

        $config = strtoupper(sprintf('__%s_CONFIG', __CONNECTER_MODE));

        $isConnect = $driver->load($taskType, $GLOBALS[$config]);
        if (!$isConnect) {
            return null;
        }

        return $driver;
    }

    /**
     * 获取所有队列名
     *
     * @return array
     * @author
     **/
    public static function getKeys()
    {
        $config = strtoupper(sprintf('__%s_CONFIG', __CONNECTER_MODE));

        $queueNames = [];

        $tasks = taskmgr_whitelist::get_all_task_list();
        foreach ($tasks as $key => $value) {
            if (false !== strpos($key, 'domainqueue')) {
                continue;
            }

            $prefix = $GLOBALS[$config]['QUEUE_PREFIX'] ?: 'ERP';

            $queueNames[] = sprintf('%s_TASK_%s_QUEUE', $prefix, strtoupper($key));
        }

        return $queueNames;
    }

    /**
     * 获取队列名
     *
     * @return string
     * @author
     **/
    public static function getKey($key)
    {
        $config = strtoupper(sprintf('__%s_CONFIG', __CONNECTER_MODE));

        $prefix = $GLOBALS[$config]['QUEUE_PREFIX'] ?: 'ERP';

        $name = sprintf('%s_TASK_%s_QUEUE', $prefix, strtoupper($key));

        return $name;
    }

    /**
     * 获取队列长度
     *
     * @return void
     * @author
     **/
    public function count($key)
    {
        $split = explode('_', $key);
        // 弹出第一，第二个
        array_shift($split); array_shift($split); array_pop($split);

        $taskType = strtolower(implode('_', $split));

        $tasks = taskmgr_whitelist::get_all_task_list();

        if (!$tasks[$taskType]) {
            taskmgr_swconsole_output::error(sprintf("队列#%s#不存在", $key) . PHP_EOL);

            exit();
        }

        $driver = self::getDriver($taskType);
        if (!is_object($driver)) {
            taskmgr_swconsole_output::error('队列服务未启用' . PHP_EOL);

            exit();
        }

        return $driver->length();
    }
}
