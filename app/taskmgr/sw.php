<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

require_once dirname(__FILE__) . '/shell/shell.php';

class sw
{
    protected $commands;

    public function __construct($commandClasses = array())
    {
        foreach (scandir(__DIR__ . '/lib/swcommand') as $value) {
            $basename = basename($value, '.php');

            if ($value != '.' && $value != '..' && $basename != 'base') {
                $commandClasses[] = 'taskmgr_swcommand_' . $basename;
            }
        }

        foreach ($commandClasses as $value) {
            foreach ($value::signature() as $k => $v) {
                $this->commands[$k] = [
                    'class'       => $value,
                    'function'    => $v,
                    'description' => $value::description()[$k] ?: '',
                ];
            }
        }
    }

    /**
     * main
     * @return mixed 返回值
     */
    public function main()
    {
        global $argv;
        if (!isset($argv[1])) {
            return taskmgr_swconsole_output::error('请输入指令 list可查看指令列表' . PHP_EOL);
        }
        $action = $argv[1];
        switch ($action) {
            case 'list':
                foreach ($this->commands as $key => $value) {
                    taskmgr_swconsole_output::normal($key . ' ' . $value['description'] . PHP_EOL);
                }
                return;
            default:
                if (isset($this->commands[$action])) {
                    return call_user_func([new $this->commands[$action]['class'], $this->commands[$action]['function']]);
                }
                break;
        }

        taskmgr_swconsole_output::error('错误的指令,请输入list查看指令列表' . PHP_EOL);
    }
}

require_once __ROOT_DIR . '/../../vendor/autoload.php';

try {
    // 兼容PHP ERROR
    if (defined('SENTRY_OPTIONS') && constant('SENTRY_OPTIONS') && is_array(constant('SENTRY_OPTIONS'))){
        \Sentry\init(constant('SENTRY_OPTIONS'));
    }
    (new sw())->main();

} catch (\Throwable $th) {
    \Sentry\captureException($th);
}
