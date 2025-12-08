<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class tgkpi_finder_reason{

    var $addon_cols = "reason_id";
    var $column_edit = "操作";
    var $column_edit_width = "100";
    function column_edit($row){
        $finder_id = $_GET['_finder']['finder_id'];
        if($row[$this->col_prefix.'reason_id'] == 1){
            return '默认不可操作';
        }else{
            return '<a href="index.php?app=tgkpi&ctl=admin_setting&act=editreason&p[0]='.$row[$this->col_prefix.'reason_id'].'&finder_id='.$finder_id.' " target="dialog::{width:450,height:150,title:\'编辑校验失败原因\'}">编辑</a>';
        }
    }
}
?>