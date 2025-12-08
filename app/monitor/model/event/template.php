<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Vsersion: 2022/10/13
 * @Describe: 预警模板model
 */
class monitor_mdl_event_template extends dbeav_model
{
    function modifier_event_type($type)
    {
        $eventTemplateLib = kernel::single('monitor_event_template');
        $eventType        = $eventTemplateLib->getEventType();
        return $eventType[$type];
    }
    
    function modifier_disabled($type)
    {
        return $type == 'true' ? '禁用' : '启用';
    }
}