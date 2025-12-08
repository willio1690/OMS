<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Vsersion: 2022/10/13
 * @Describe: 预警配置model
 */
class monitor_mdl_event_receiver extends dbeav_model
{
    public function _filter($filter,$tableAlias=null,$baseWhere=null)
    {
        if ($filter['event_type']) {
            $findSql = '';
            foreach ($filter['event_type'] as $type) {
                $findSql .= "FIND_IN_SET('".$type."',event_type) AND ";
            }
            $filter['filter_sql'] = mb_substr($findSql,0,mb_strlen($findSql)-4);
            unset($filter['event_type']);
        }
        return parent::_filter($filter,$tableAlias,$baseWhere);
    
    }
    function modifier_event_type($type)
    {
        $eventTemplateLib = kernel::single('monitor_event_template');
        $eventType        = $eventTemplateLib->getEventType();
        $type = explode(',',$type);
        $typeName = '';
        if ($type) {
            foreach ($type as $name) {
                $typeName .= $eventType[$name].'，';
            }
        }
        
        return mb_substr($typeName,0,mb_strlen($typeName)-1);
    }
    
    function modifier_org_id($org_id)
    {
        $operationOrg = app::get('ome')->model('operation_organization')->getList('org_id,name',['org_id'=>explode(',', $org_id)]);
        $orgList        = implode(',',array_column($operationOrg,'name'));
        return $orgList;
    }
}