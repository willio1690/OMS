<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Vsersion: 2022/10/13
 * @Describe: 预警模板finder类
 */
class monitor_finder_event_template
{
    var $column_edit = '操作';
    var $column_edit_width = "75";
    
    function column_edit($row)
    {
        $finder_vid = $_GET['finder_vid'];
        $btn = '<a href="index.php?app=monitor&ctl=admin_alarm_template&act=edit&p[0]=' . $row['template_id'] . '&finder_id=' . $_GET['_finder']['finder_id'] . '&finder_vid=' . $finder_vid . '" >编辑</a>&nbsp;&nbsp;';

        return $btn;
    }
    var $detail_basic = '基本信息';
    function detail_basic($template_id){
        $render = app::get('ome')->render();
        $eventTemplateMdl = app::get('monitor')->model('event_template');
        $templateInfo = $eventTemplateMdl->db_dump($template_id);
        $render->pagedata['templateInfo'] = $templateInfo;
        return $render->fetch("admin/alarm/event/template/template_detail.html",'monitor');
    }
}