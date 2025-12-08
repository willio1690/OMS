<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_finder_bank_account
{

	var $column_edit = '操作';
	var $column_edit_width = "100";

    /**
     * column_edit
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_edit($row)
	{
		$finder_id = $_GET['_finder']['finder_id'];
		return '<a href="index.php?app=ome&ctl=admin_setting&act=add_bank_account&ba_id=' . $row['ba_id'] . '&finder_id='.$finder_id.'" target="dialog::{width:550,height:350,resizeable:false,title:\'编辑异常类型\'}">编辑</a>';
	}

}