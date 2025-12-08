<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 企业组织管理finder
 */
class organization_finder_organization{

	var $addon_cols = "del_mark";
	var $column_edit = '操作';
	var $column_edit_width = "100";
	function column_edit($row){
		//删除状态的组织层级目前不做操作
		if(intval($row[$this->col_prefix."del_mark"]) == 1){
			return '';
		}
		$org_id = $row["org_id"];
		$find_id = $_GET['_finder']['finder_id'];
		$view = $_GET["view"]?$_GET["view"]:0;
		$button = <<<EOF
			<a href="index.php?app=organization&ctl=admin_management&act=editGropItem&finder_id=$find_id&org_id=$org_id&view=$view" target="dialog::{width:550,height:400,title:'编辑组织'}">编辑</a>&nbsp;
EOF;
		$button.= <<<EOF
		<a href="javascript:if(confirm('确认删除该组织？')){W.page('index.php?app=organization&ctl=admin_management&act=doDelGropItem&finder_id=$find_id&org_id=$org_id&view=$view', $extend({method: 'get'}, JSON.decode({})), this);}void(0);" target="">删除</a>
EOF;
		if(intval($row["status"]) == 2){
			$button.= <<<EOF
			<a href="index.php?app=organization&ctl=admin_management&act=doActiveGropItem&finder_id=$find_id&org_id=$org_id&view=$view" target="">启用</a>
EOF;
		}
		if(intval($row["status"]) == 1){
			$button.= <<<EOF
			<a href="javascript:if(confirm('确认停用该组织？')){W.page('index.php?app=organization&ctl=admin_management&act=doUnactiveGropItem&finder_id=$find_id&org_id=$org_id&view=$view', $extend({method: 'get'}, JSON.decode({})), this);}void(0);" target="">停用</a>
EOF;
		}
		return $button;
	}
	
}