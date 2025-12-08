<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/12/4 11:49:52
 * @describe: 费用均摊
 * ============================
 */
class financebase_finder_expenses_unsplit {
    public $addon_cols = 'bill_category';

    public $column_edit = "操作";
    public $column_edit_width = "80";
    public $column_edit_order = 1;
    /**
     * column_edit
     * @param mixed $row row
     * @return mixed 返回值
     */

    public function column_edit($row) {
        $finder_id = $_GET['_finder']['finder_id'];
        $ret = '<a href="index.php?app=financebase&ctl=admin_expenses_splititem&act=split&p[0]='.$row['id'].'&finder_id=' . $finder_id . '&view='.intval($_GET['view']).'" target="dialog::{width:350,height:200,title:\'再次拆分\'}">再次拆分</a>';

        return $ret;
    }
}