<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class pam_mdl_log extends dbeav_model
{
	public $appendCols = 'event_id,event_time,event_data,event_type';
	
	function searchOptions(){
        $childOptions = array(
            'op_name'=>app::get('ome')->_('用户名'),
        );
		return $childOptions;
    }

	function _filter($filter,$tableAlias=null,$baseWhere=null)
    {
        $tPre      = ($tableAlias ? $tableAlias : '`' . $this->table_name(true) . '`') . '.';
        $tmpBaseWhere = kernel::single('ome_filter_encrypt')->encrypt($filter, $this->__encrypt_cols, $tPre, 'orders');
        $baseWhere = $baseWhere ? array_merge((array)$baseWhere, (array)$tmpBaseWhere) : (array)$tmpBaseWhere;
       
		$where = " 1 ";
        if(isset($filter['op_name'])){
            $where  .= ' and event_data like "%'.$filter['op_name'].'%" ';
            unset($filter['op_name']);
        }
        
        return $where ." AND ".parent::_filter($filter,$tableAlias,$baseWhere);
    }

}
