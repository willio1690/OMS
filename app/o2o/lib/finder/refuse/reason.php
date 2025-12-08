<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_finder_refuse_reason
{
    var $addon_cols = 'reason_id';
    var $column_edit = '操作';
    var $column_edit_width = '100';
    function column_edit($row)
    {
        $finder_id    = $_GET['_finder']['finder_id'];
        return '<a href="index.php?app=o2o&ctl=admin_refuse_reason&act=edit&p[0]='.$row[$this->col_prefix.'reason_id'].'&finder_id='.$finder_id.' " target="dialog::{width:450,height:150,title:\'编辑拒单原因\'}">编辑</a>';
    }
}
?>