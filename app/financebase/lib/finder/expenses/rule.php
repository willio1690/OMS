<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/11/24 10:05:23
 * @describe: 费用均摊规则
 * ============================
 */
class financebase_finder_expenses_rule {

    public $column_edit = "操作";
    public $column_edit_width = "80";
    /**
     * column_edit
     * @param mixed $row row
     * @return mixed 返回值
     */

    public function column_edit($row) {
        $finder_id = $_GET['_finder']['finder_id'];

        $ret = '<a href="index.php?app=financebase&ctl=admin_expenses_rule&act=setRule&p[0]='.$row['rule_id'].'&finder_id=' . $finder_id . '" target="dialog::{width:550,height:400,resizeable:false,title:\'设置\'}">设置</a>';

        return $ret;
    }
}