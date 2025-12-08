<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 多进程任务执行命令
 *
 * @author chenping@shopex.cn
 * @version Fri May  6 23:15:31 2022
 */
class taskmgr_swcommand_base
{
    static protected $signature = [];
    static protected $description = [];

    public static function signature()
    {
        return static::$signature;
    }

    public static function description()
    {
        return static::$description;
    }
}
