<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class taskmgr_swprocess_conf
{
    private static $daemon = false;

    public static function setDaemon()
    {
        self::$daemon = true;
    }

    public static function __callStatic($name, $arg)
    {
        return self::${$name};
    }
}
