<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Date: 2023/5/18
 * @Describe: 预警配置邮件组finder类
 */
class monitor_finder_event_group
{
    var $column_edit = '操作';
    var $column_edit_width = "75";
    var $column_edit_order = "5";
    
    function column_edit($row)
    {
        $btn = '<a href="index.php?app=monitor&ctl=admin_alarm_group&act=edit&p[0]=' . $row['group_id'] . '&finder_id=' . $_GET['_finder']['finder_id'] . '" target="dialog::{width:800,height:630,title:\'编辑收件人\'}" >编辑</a>&nbsp;&nbsp;';
        
        return $btn;
    }
    
    var $detail_basic = '详情';
    function detail_basic($group_id)
    {
        $render                     = app::get('ome')->render();
        $eventGroupMdl              = app::get('monitor')->model('event_group');
        $eventGroupTempMdl          = app::get('monitor')->model('event_group_template');
        $data                       = $eventGroupMdl->db_dump(array('group_id' => $group_id), '*');
        $event                      = $eventGroupTempMdl->db_dump(['group_id' => $group_id]);
        $data['event_type']         = explode(',', $event['event_type']);
        $data['receiver_id']        = explode(',', $data['receiver_id']);
        $data['org_id']             = explode(',', $data['org_id']);
        $render->pagedata['detail'] = $data;
    
        $eventType = kernel::single('monitor_event_template')->getEventType();
        foreach ($data['event_type'] as $val) {
            $eventList[] = $eventType[$val];
        }
        $render->pagedata['event_type_list'] = implode('<br>',$eventList);
    
        $receiverList = app::get('monitor')->model('event_receiver')->getList('id,receiver',['id'=>$data['receiver_id']]);

        $render->pagedata['receiver_list'] = implode('<br>',array_column($receiverList,'receiver'));
    
    
    
        return $render->fetch('admin/alarm/event/group_detail_basic.html','monitor');
    }
}