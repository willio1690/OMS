<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 销售物料字段物料规格
 * @author
 * @version 1.0
 */

class material_extracolumn_basicmaterial_specifications extends desktop_extracolumn_abstract implements desktop_extracolumn_interface{

	protected $__pkey = 'bm_id';
	protected $__extra_column = 'column_specifications';

	/**
	 * 获取物料规格
	 * @params $ids
	 * @return array()
	 */
	public function associatedData($ids){
		$material_ext = app::get('material')->model('basic_material_ext');
		$specifications = $material_ext->getList('bm_id,specifications', array($this->__pkey=>$ids));

		$tmp_array= array();
		foreach($specifications as $k=>$row){
			$tmp_array[$row[$this->__pkey]] = $row['specifications'];
		}
		return $tmp_array;
	}

}