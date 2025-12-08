<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_finder_operation_organization {
    var $addon_cols = '';

    function __construct($app){
        $this->app=$app;
    }

    var $column_control = '操作';
    function column_control($row){
        $finder_id = $_GET['_finder']['finder_id'];
        return '<a href="index.php?app=ome&ctl=admin_operationorg&act=edit&p[0]=' . $row['org_id'] . '&_finder[finder_id]=' . $finder_id . '&finder_id=' . $finder_id . '" target="dialog::{width:600,height:400,title:\'编辑运营组织\'}">编辑</a>';
    }
}