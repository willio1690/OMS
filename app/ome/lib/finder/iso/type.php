<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @describe: 出入库类型
 * ============================
 */
class ome_finder_iso_type {
    public $addon_cols = "";
    public $column_edit = "操作";
    public $column_edit_width = 120;
    public $column_edit_order = 1;
    /**
     * column_edit
     * @param mixed $row row
     * @return mixed 返回值
     */

    public function column_edit($row){
        $btn = [];
        $btn[] = '<a class="lnk" target="dialog::{width:600,height:300,title:\'编辑\'}" href="index.php?app=ome&ctl=admin_iso_type&act=addEdit&p[0]='.$row['id'].'&finder_id='.$_GET['_finder']['finder_id'].'&finder_vid='.$_GET['finder_vid'].'">编辑</a>';
        return implode('|', $btn);
    }

}