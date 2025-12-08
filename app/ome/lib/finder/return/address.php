<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_finder_return_address
{
    function __construct($app)
    {
        $this->app = $app;
    }
    
    var $column_edit = "操作";
    var $column_edit_width = "200";
    function column_edit($row)
    {
        if (in_array($row['shop_type'],array('xhs'))) {
            return '<a target="dialog::{width:700,height:400,title:\'编辑\'}" href="index.php?app=ome&ctl=admin_return_address&act=edit&p[0]=' . $row['address_id'] . '&finder_id=' . $_GET['_finder']['finder_id'] . '">编辑</a>  ';
        }
    }
}

?>