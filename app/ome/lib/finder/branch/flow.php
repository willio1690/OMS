<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_finder_branch_flow
{
    public $column_opt       = "操作";
    public $column_opt_order = 1;
    /**
     * column_opt
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_opt($row)
    {
        $buttons['edit'] = sprintf("<a href='index.php?app=ome&ctl=admin_branch_flow&act=edit&p[]=%s&finder_id=%s' target='dialog::{width:600,height:300,title:\"仓业务设置\"}'>编辑</a>", $row['id'], $_GET['_finder']['finder_id']);

        return implode(' ', $buttons);
    }

}
