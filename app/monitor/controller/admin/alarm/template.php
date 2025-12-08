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
class monitor_ctl_admin_alarm_template extends desktop_controller
{
    
    function index()
    {
        $actions   = array();
        $actions[] = array(
            'label'  => '新增',
            'href'   => 'index.php?app=monitor&ctl=admin_alarm_template&act=add',
        );
        
        $params = array(
            'title'               => '预警模板',
            'actions'             => $actions,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => false,
        );
        
        $this->finder('monitor_mdl_event_template', $params);
    }
    
    //新增弹窗页
    function add()
    {
        $this->_edit('add');
    }
    
    //编辑弹窗页
    function edit($id)
    {
        $this->_edit('edit', $id);
    }
    
    //新增和编辑弹窗页的展示
    function _edit($action, $id = 0)
    {
        $eventTemplateMdl = app::get('monitor')->model('event_template');
        $data             = array();
        
        if ($id) {
            $data = $eventTemplateMdl->dump(array('template_id' => $id), '*');
        }
        $eventTemplateLib = kernel::single('monitor_event_template');
        
        //模板类型
        $eventType                   = $eventTemplateLib->getEventType();
        $this->pagedata['eventType'] = $eventType;
        // 默认模板内容映射
        $this->pagedata['eventDefaultContent'] = kernel::single('monitor_event_template')->getEventDefaultContent();
        $this->pagedata['data']      = $data;
        $sendType = $eventTemplateMdl->schema['columns']['send_type']['type'];
        $this->pagedata['send_type'] = $sendType;
//        $pmtKeywords = app::get('monitor')->getConf('monitor.check.pmt.name');
//        $this->pagedata['pmt_keywords'] = $pmtKeywords;
        $this->page('admin/alarm/event/template/addTemplate.html');
    }
    
    //保存
    function save()
    {
        $this->begin('index.php?app=monitor&ctl=admin_alarm_template&act=index');
        
        $eventTemplateMdl = app::get('monitor')->model('event_template');
        
        $data = $_POST;
        
        if (empty($data['template_bn']) || empty($data['template_name'])) {
            $this->end(false, '请填写模板名称和模板编号，请检查!');
        }
        
        if ($data['send_type'] == 'sms' && !$data['template_id']) {
            $data['status'] = '0';
        }
        
        if (empty($data['content'])) {
            $this->end(false, '模板内容不能为空，请检查!');
        }
//        $data['content'] = htmlspecialchars($data['content']);
        $data['content'] = str_replace('<script>','',$data['content']);
        $data['content'] = str_replace('</script>','',$data['content']);
    
    
        if ($data['template_id']) {
            $data['template_id'] = intval($data['template_id']);
        }
        
        if (!$data['template_id']) {
            $row = $eventTemplateMdl->db_dump(array(
                'event_type' => $data['event_type'],
                'send_type'  => $data['send_type']
            ));
            if ($row) {
                $this->end(false, '模板类型不能重复，请检查!');
            }
        }
        
        $row = $eventTemplateMdl->db_dump(array('template_bn' => $data['template_bn']), '*');
        
        if ($row) {
            if ($data['template_id'] && ($row['template_id'] != $data['template_id'])) {
                $this->end(false, '模板编码不能重复，请检查!');
            } elseif (empty($data['template_id'])) {
                $this->end(false, '模板编码不能重复，请检查!');
            }
        }
        if ($data['pmt_name']) {
            app::get('monitor')->setConf('monitor.check.pmt.name',trim($data['pmt_name']));
        }
        $result = $eventTemplateMdl->save($data);
        if (!$result) {
            $this->end(false, '保存失败');
        }
        
        $this->end(true, '保存成功');
    }
}
