<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 销售物料字段物料品牌
 * @author
 * @version 1.0
 */

class material_extracolumn_basicmaterial_brand extends desktop_extracolumn_abstract implements desktop_extracolumn_interface{

	protected $__pkey = 'bm_id';
	protected $__extra_column = 'column_brand';

	/**
	 * 获取物料品牌
	 * @params $ids
	 * @return array()
	 */
	public function associatedData($ids){
		$material_ext = app::get('material')->model('basic_material_ext');
		$ome_brand = app::get('ome')->model('brand');
		$brand_obj = $material_ext->getList($this->__pkey.',brand_id',array($this->__pkey=>$ids));

		foreach($brand_obj as $k => $val){
			$brand_name	= $ome_brand->getList('brand_name', array('brand_id' => $val['brand_id']));
			$brand_obj[$k]['brand_name'] = $brand_name[0]['brand_name'];
		}

		$tmp_array = array();
		foreach($brand_obj as $key => $row){
			$tmp_array[$row[$this->__pkey]] = $row['brand_name'];
		}

		return $tmp_array;

	}
}
