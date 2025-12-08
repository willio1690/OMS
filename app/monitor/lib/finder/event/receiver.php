<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Vsersion: 2022/10/13
 * @Describe: 预警配置finder类
 */
class monitor_finder_event_receiver
{
    var $column_edit = '操作';
    var $column_edit_width = "75";
    var $column_edit_order = "10";
    function column_edit($row)
    {
        $btn = '<a href="index.php?app=monitor&ctl=admin_alarm_receiver&act=edit&p[0]=' . $row['id'] . '&finder_id=' . $_GET['_finder']['finder_id'] . '" target="dialog::{width:800,height:630,title:\'编辑收件人\'}" >编辑</a>&nbsp;&nbsp;';
        
        return $btn;
    }
}