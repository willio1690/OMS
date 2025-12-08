<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_finder_server
{

    public $addon_cols        = "server_id";
    public $column_edit       = "操作";
    public $column_edit_width = 120;
    public $column_edit_order = 1;
    /**
     * column_edit
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_edit($row)
    {
        $finder_id = $_GET['_finder']['finder_id'];
        $server_id = $row[$this->col_prefix . 'server_id'];
        $button    = '<a href="index.php?app=o2o&ctl=admin_server&act=edit&p[0]=' . $server_id . '&finder_id=' . $finder_id . '" target="dialog::{width:650,height:450,title:\'编辑服务端\'}">编辑</a>';
        return $button;
    }

}
