<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Date: 2023/5/18
 * @Describe: 邮件组
 */
class monitor_ctl_admin_alarm_group extends desktop_controller
{
    
    function index()
    {
        $actions   = array();
        $actions[] = array(
            'label' => '新增',
            'href'  => 'index.php?app=monitor&ctl=admin_alarm_group&act=add',
        );
        
        $params = array(
            'title'               => '邮件组配置',
            'actions'             => $actions,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => true,
            'use_buildin_filter'  => true,
        );
        
        $this->finder('monitor_mdl_event_group', $params);
    }
    
    function add()
    {
        $this->_edit('add');
    }
    
    function edit($id)
    {
        $this->_edit('edit', $id);
    }
    
    function _edit($action, $id = 0)
    {
        $eventTemplateMdl = app::get('monitor')->model('event_template');
        $eventGroupMdl = app::get('monitor')->model('event_group');
        $eventGroupTempMdl = app::get('monitor')->model('event_group_template');
        $data             = array();
        
        if ($id) {
            $data               = $eventGroupMdl->db_dump(array('group_id' => $id), '*');
            $event = $eventGroupTempMdl->db_dump(['group_id'=>$id]);
            $data['event_type'] = explode(',', $event['event_type']);
            $data['receiver_id'] = explode(',', $data['receiver_id']);
            $data['org_id']     = explode(',', $data['org_id']);
        }

        //模板类型
        $templateList = $eventTemplateMdl->getList('template_id,template_name', ['status' => 1]);
        
        $this->pagedata['templateList'] = array_column($templateList, 'template_name', 'template_id');
        $eventTemplateLib               = kernel::single('monitor_event_template');
        $eventType                      = $eventTemplateLib->getEventType();
        
        $operationOrg = app::get('ome')->model('operation_organization')->getList('org_id,name', ['status' => '1']);
        $receiverList = app::get('monitor')->model('event_receiver')->getList('id,receiver');
        
        $this->pagedata['data']          = $data;
        $this->pagedata['eventType']     = $eventType;
        $this->pagedata['action']        = $action;
        $this->pagedata['org_list']      = $operationOrg;
        $this->pagedata['receiver_list'] = $receiverList;
        
        $this->display('admin/alarm/event/addGroup.html');
    }
    
    //保存
    function save()
    {
        $this->begin('index.php?app=monitor&ctl=admin_alarm_group&act=index');
        
        $eventGroupMdl     = app::get('monitor')->model('event_group');
        $eventGroupTempMdl = app::get('monitor')->model('event_group_template');
        
        $data = $_POST;
        
        $orgIds = '';
        if ($data['org_id']) {
            $orgIds = implode(',', $data['org_id']);
        }
        $receiverIds = '';
        if ($data['receiver_id']) {
            $receiverIds = implode(',', $data['receiver_id']);
        }
        
        $eventTypes = '';
        if ($data['event_type']) {
            $eventTypes = implode(',', $data['event_type']);
        }
        
        $updateData = [
            'group_name'  => $data['group_name'],
            'receiver_id' => $receiverIds,
            'org_id'      => $orgIds,
            'disabled'    => $data['disabled'],
        ];
        if ($data['group_id']) {
            $updateData['group_id'] = $data['group_id'];
        }
        
        $res = $eventGroupMdl->save($updateData);
        if (!$res) {
            $this->end(false, '更新失败：' . $data['group_name']);
        }
        $groupTmepData['group_id'] = $updateData['group_id'];
        $groupTmepData['event_type'] = $eventTypes;
        
        $eventGroupTempMdl->save($groupTmepData);
        
        $this->end(true, '保存成功');
    }
}
