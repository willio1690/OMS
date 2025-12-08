<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class invoice_finder_order_setting
{
	public $addon_cols = 'title,tax_rate,shop_id';//调用字段
	
	/*------------------------------------------------------ */
    //-- 编辑
    /*------------------------------------------------------ */
	var $column_edit  = '编辑';
    var $column_edit_order = 5;
    var $column_edit_width = '60';
    function column_edit($row)
    {
        $finder_id = $_GET['_finder']['finder_id'];
        $sid = $row['sid'];

        $button = "<a href='index.php?app=invoice&ctl=admin_order_setting&act=editor&p[0]={$sid}&finder_id={$finder_id}' target='dialog::{width:620,height:585,title:\"开票信息配置编辑\"}'>编辑</a>";

        return $button;
    }
}