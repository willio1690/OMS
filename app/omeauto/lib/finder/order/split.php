<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeauto_finder_order_split {
    
    var $addon_cols = "";
    
    var $column_edit = '操作';
    var $column_edit_width = "80";
    var $column_edit_order = "5";
    function column_edit($row) {
        $btn = "<a href='javascript:void(0);' onclick=\"new Dialog('index.php?app=omeauto&ctl=order_split&act=edit&p[0]={$row['sid']}&finder_id={$_GET['_finder']['finder_id']}',{width:760,height:480,title:'修改拆单规则'}); \">修改</a>";
        return $btn;
    }
}