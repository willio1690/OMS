<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeauto_finder_branchgroup {
    public $addon_cols = "branch_group";
    public $column_edit = '操作';
    public $column_edit_width = "100";
    public $column_edit_order = 1;
    /**
     * column_edit
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_edit($row) {
        $btn = '';
        $btn .= "<a href='javascript:void(0);' onclick=\"new Dialog('index.php?app=omeauto&ctl=branchgroup&act=edit&bg_id={$row['bg_id']}&finder_id={$_GET['_finder']['finder_id']}',{width:760,height:400,title:'修改仓库分组'}); \">编辑</a>";
        return $btn;
    }

    public $column_branch_group = '仓库';
    public $column_branch_group_width = "200";
    public $column_branch_group_order = 1;
    /**
     * column_branch_group
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_branch_group($row) {
        $branchGroup = app::get('ome')->model('branch')->getList('name', array('branch_id'=>explode(',', $row[$this->col_prefix.'branch_group'])));
        return implode(',', array_map('current', $branchGroup));
    }
}