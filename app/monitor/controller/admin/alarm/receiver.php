<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Vsersion: 2022/10/13
 * @Describe: 监控预警控制
 */
class monitor_ctl_admin_alarm_receiver extends desktop_controller
{
    
    function index()
    {
        $actions   = array();
        $actions[] = array(
            'label' => '新增',
            'href'  => 'index.php?app=monitor&ctl=admin_alarm_receiver&act=add',
        );
        
        $params = array(
            'title'               => '收件人配置',
            'actions'             => $actions,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => true,
            'use_buildin_filter'=>true,
        );
        
        $this->finder('monitor_mdl_event_receiver', $params);
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
        $eventReceiverMdl = app::get('monitor')->model('event_receiver');
        $data             = array();
    
        if ($id) {
            $data               = $eventReceiverMdl->dump(array('id' => $id), '*');
            $data['event_type'] = explode(',', $data['event_type']);
            $data['org_id']     = explode(',', $data['org_id']);
        }
        
        //模板类型
        $templateList = $eventTemplateMdl->getList('template_id,template_name', ['status' => 1]);
        
        $this->pagedata['templateList'] = array_column($templateList, 'template_name', 'template_id');
        $eventTemplateLib = kernel::single('monitor_event_template');
        $eventType        = $eventTemplateLib->getEventType();
        
        $operationOrg = app::get('ome')->model('operation_organization')->getList('org_id,name',['status'=>'1']);

        $this->pagedata['data'] = $data;
        $this->pagedata['eventType'] = $eventType;
        $this->pagedata['action'] = $action;
        $this->pagedata['org_list'] = $operationOrg;
        
        $this->display('admin/alarm/event/addReceiver.html');
    }
    
    //保存
    function save()
    {
        $this->begin('index.php?app=monitor&ctl=admin_alarm_receiver&act=index');
        
        $eventReceiverMdl = app::get('monitor')->model('event_receiver');
        
        $data = $_POST;
        
//        if (empty($data['event_type'])) {
//            $this->end(false, '请选择触发事件，请检查!');
//        }
        
        if (empty($data['receiver'])) {
            $this->end(false, '请填写接收信息地址，请检查!');
        }
        
        foreach ($data['receiver'] as $val) {
            if ($data['id']) {
                $filter = ['id'=>$data['id']];
            }else{
                $filter = ['receiver'=>$val];
            }
            
            $sendType = '';
            $isRight = false;
            if (preg_match("/^1[3456789]\d{9}$/", $val)) {
                $isRight = true;
                $sendType = 'sms';
            }
            if (preg_match("/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i", $val)) {
                $isRight = true;
                $sendType = 'email';
            }
            if (!$isRight) {
                $this->end(false, '接收信息地址内容不正确，请填写正确的手机号或者邮箱，请检查!' . $val);
            }
            
            $row = $eventReceiverMdl->dump($filter, '*');
            
            $orgIds = '';
            if ($data['org_id']) {
                $orgIds = implode(',', $data['org_id']);
            }
            if ($row) {
                $eventTYpe = is_array($data['event_type']) ? implode(',', $data['event_type']) : '';
                $updateData = [
                    'receiver'   => $val,
                    'event_type' => $eventTYpe,
                    'send_type'  => $sendType,
                    'org_id'     => $orgIds,
                ];
                $res = $eventReceiverMdl->update($updateData,['id'=>$row['id']]);
                if (!$res) {
                    $this->end(false, '更新失败：'.$val);
                }
            }else{
//                $eventTYpe = implode(',', array_unique($data['event_type']));
                $insertData = [
                    'receiver'   => $val,
                    'event_type' => '',
                    'send_type'  => $sendType,
                    'org_id'     => $orgIds,
                ];
                $res = $eventReceiverMdl->insert($insertData);
                if (!$res) {
                    $this->end(false, '保存失败：'.$val);
                }
            }
        }
        $this->end(true, '保存成功');
    }
}
