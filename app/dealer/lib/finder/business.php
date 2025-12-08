<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/9/4 16:22:23
 * @describe: 经销商
 * ============================
 */
class dealer_finder_business {

    public $addon_cols = "bs_id";
    public $column_edit = "操作";
    public $column_edit_width = 120;
    public $column_edit_order = 1;
    /**
     * column_edit
     * @param mixed $row row
     * @return mixed 返回值
     */

    public function column_edit($row){
        $finder_id = $_GET['_finder']['finder_id'];
        $bs_id = $row[$this->col_prefix.'bs_id'];
        $button = '<a href="index.php?app=dealer&ctl=admin_business&act=edit&p[0]='.$bs_id.'&finder_id='.$finder_id.'" target="_blank">编辑</a>';
        return $button;
    }
}