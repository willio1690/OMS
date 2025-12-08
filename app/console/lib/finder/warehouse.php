<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_finder_warehouse{
    
    var $addon_cols    = 'branch_id';
    
    var $column_edit  = '操作';
    var $column_edit_order = 2;
    var $column_edit_width = '100';
    function column_edit($row)
    {
        $finder_id   = $_GET['_finder']['finder_id'];
        $branch_id   = $row[$this->col_prefix .'branch_id'];
        
        $button = '<a href="index.php?app=console&ctl=admin_warehouse&act=edit&p[0]='. $branch_id .'&_finder[finder_id]='. $finder_id .'&finder_id='. $finder_id .'" target="_blank">编辑</a>';
        
        return '<span class="c-gray">'. $button .'</span>';
    }
    
    var $detail_basic = "仓库详情";
    function detail_basic($branch_id)
    {
        $render    = app::get('console')->render();
        $branchObj = app::get('console')->model('warehouse');
        
        $render->pagedata['branch'] = $branchObj->dump($branch_id);
        
        return $render->fetch('admin/vop/warehouse_detail.html');
    }
}