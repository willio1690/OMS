<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 基础物料字段成本价
 * @author xiayuanjun@shopex.cn
 * @version 1.0
 */
class material_extracolumn_basicmaterial_barcode extends desktop_extracolumn_abstract implements desktop_extracolumn_interface{

    protected $__pkey = 'bm_id';
    protected $__extra_column = 'column_barcode';

    /**
     * 
     * 获取基础物料字段成本价
     * @param $ids
     * @return array $tmp_array关联数据数组
     */

    public function associatedData($ids){
        
        $basicMaterialCodeObj = app::get('material')->model('codebase');
        $barcode_lists = $basicMaterialCodeObj->getList('code,'.$this->__pkey,array($this->__pkey => $ids, 'type' => material_codebase::getBarcodeType()));

        $tmp_array= array();
        foreach($barcode_lists as $k=>$row){
             $tmp_array[$row[$this->__pkey]] = $row['code'];
        }
        return $tmp_array;
    }

}