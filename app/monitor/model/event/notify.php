<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Vsersion: 2022/10/13
 * @Describe: 预警通知model
 */
class monitor_mdl_event_notify extends dbeav_model
{
    function modifier_event_type($type)
    {
        $eventTemplateLib = kernel::single('monitor_event_template');
        $eventType        = $eventTemplateLib->getEventType();
        return $eventType[$type];
    }
    
    function modifier_is_sync($is_sync)
    {
        return $is_sync == 'true' ? '立即发送' : '异步发送';
    }
    
    public function _filter($filter, $tableAlias = null, $baseWhere = null)
    {
        $where = '';
        if ($filter['send_content']) {
            $filter['send_content|has'] = $filter['send_content'];
            unset($filter['send_content']);
        }
        
        return parent::_filter($filter, $tableAlias, $baseWhere) . $where;
    }
}