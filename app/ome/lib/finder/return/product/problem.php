<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_finder_return_product_problem{
    var $detail_basic = "售后问题详情";
    
    function detail_basic($problem_id){
        $render = app::get('ome')->render();
        $oProblem = app::get('ome')->model("return_product_problem");
        $problem = $oProblem->dump($problem_id);
        $render->pagedata['problem'] = $problem;
        return $render->fetch("admin/system/product_problem_detail.html");

    }
    
    var $addon_cols = "problem_id";
    var $column_edit = "操作";
    var $column_edit_width = "100";
    var $column_edit_order = 1;
    function column_edit($row)
    {
        $finder_id = $_GET['_finder']['finder_id'];
        $url = "index.php?app=ome&ctl=admin_setting&act=%s&p[0]=".$row[$this->col_prefix.'problem_id'].'&finder_id='.$finder_id;
        
        $button= '';
        if($row['defaulted'] == 'true'){
            $button_url = sprintf($url, 'problemWipeDefaulted');
            $button .= "<a href='javascript:void(0);' target='download' onclick='if(confirm(\"你确定设置要取消默认么？\")) {href=\"{$button_url}\";}'>取消默认</a>";
        }else{
            $button_url = sprintf($url, 'problemSetDefaulted');
            $button .= "<a href='javascript:void(0);' target='download' onclick='if(confirm(\"你确定设置为默认么？\")) {href=\"{$button_url}\";}'>默认</a>";
        }
        
        $button_url = sprintf($url, 'editproblem');
        $button .= '&nbsp;|&nbsp;<a href="'. $button_url .'" target="dialog::{width:500,height:300,title:\'编辑售后原因\'}">编辑</a>';
        
        return $button;
    }
}
?>
