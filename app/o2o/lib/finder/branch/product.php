<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_finder_branch_product {
    public $addon_cols = 'id';
    
    var $column_edit = "操作";
    var $column_edit_width = "100";
    var $column_edit_order = "1";
    function column_edit($row) {
        $link_arr = array();
        $id = $row['id'];
        $finder_id = $_GET['_finder']['finder_id'];
        
        //配置
        $config_btn = '<a href="index.php?app=o2o&ctl=admin_branch_product&act=setConfig&id='.$id.'&finder_id='.$finder_id.'" target="dialog::{width:300,height:150,title:\'配置\'}">配置</a>';
        $link_arr[] = $config_btn;
        
//         $is_bind = '<a href="index.php?app=o2o&ctl=admin_branch_product&act=is_bind&finder_id='.$finder_id.'" target="dialog::{width:800,height:630,title:\'编辑发票信息\'}">同步</a>';
//         $link_arr[] = $is_bind;
        
        return implode(" | ",$link_arr);
    }

}

?>