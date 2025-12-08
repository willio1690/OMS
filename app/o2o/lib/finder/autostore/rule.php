<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_finder_autostore_rule {

    var $addon_cols = "rule_id,branch_id";

    var $column_edit = "操作";
    var $column_edit_width = "100";
    var $column_edit_order = "1";
    function column_edit($row) {
        $finder_id = $_GET['_finder']['finder_id'];
        $ret= "&nbsp;<a href='index.php?app=o2o&ctl=admin_autostore&act=editRule&p[0]={$row[rule_id]}&finder_id={$finder_id}' target=\"_blank\">编辑</a>";
        return $ret;
    }

}

?>