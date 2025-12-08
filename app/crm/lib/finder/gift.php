<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class crm_finder_gift{
//    var $addon_cols = "status";
//    var $column_control = '操作';
//    var $column_control_width = "80";
//    function column_control($row){
//        $find_id = $_GET['_finder']['finder_id'];
//        if($row[$this->col_prefix . 'status'] == 2){
//            $btn = "<a href='javascript:void(0);' target='download' onclick='if(confirm(\"你确定要启用当前的赠品吗？\")) {href=\"index.php?app=crm&ctl=admin_gift&act=setStatus&p[0]={$row[gift_id]}&p[1]=true&finder_id={$_GET[_finder][finder_id]}\";}'>启用</a>";
//        }else{
//            $btn = "<a href='javascript:void(0);' target='download' onclick='if(confirm(\"你确定要暂停当前的赠品吗？\")) {href=\"index.php?app=crm&ctl=admin_gift&act=setStatus&p[0]={$row[gift_id]}&p[1]=false&finder_id={$_GET[_finder][finder_id]}\";}'>暂停</a>";
//        }
//
//        return $btn;
//    }

//    var $column_status = '当前状态';
//    var $column_status_width = "100";
//    var $column_status_order = "200";
//    function column_status($row){
//        if($row[$this->col_prefix . 'status'] == 2){
//            return '关闭';
//        }else{
//            return '启用';
//        }
//    }

    var $column_edit = '操作';
    var $column_edit_width = 120;
    function column_edit($row)
    {
        $finder_id = $_GET['_finder']['finder_id'];
        $gift_id = $row[$this->col_prefix.'gift_id'];

        $button1 = '<a href="index.php?app=crm&ctl=admin_gift&act=edit&p[0]='.$gift_id.'&finder_id='.$finder_id.'" target="dialog::{width:600,height:350,title:\'赠品设置\'}">设置赠品</a>';

        return $button1;
    }

}
?>
