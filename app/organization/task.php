<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class organization_task
{
    function post_install($options)
    {
        //初始化使用五级地区
        $area_depth    = 5;
        app::get('eccommon')->setConf('system.area_depth', $area_depth);
    }
    
    function post_uninstall()
    {
        //还原使用三级地区
        $area_depth    = 3;
        app::get('eccommon')->setConf('system.area_depth', $area_depth);
    }

}
