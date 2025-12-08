<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 基础物料字段体积
 * @author db
 * @version 1.0
 */
class material_extracolumn_basicmaterial_volume extends desktop_extracolumn_abstract implements desktop_extracolumn_interface{

    protected $__pkey = 'bm_id';
    protected $__extra_column = 'column_volume';

    /**
     * 获取基础物料字段体积
     * @param $ids
     * @return array $tmp_array关联数据数组
     */
    public function associatedData($ids){
        //根据发货单ids获取相应的备注信息
        $basicMaterialExtObj = app::get('material')->model('basic_material_ext');
        $volume_lists = $basicMaterialExtObj->getList('volume,'.$this->__pkey,array($this->__pkey => $ids));

        $tmp_array= array();
        foreach($volume_lists as $k=>$row){
             $tmp_array[$row[$this->__pkey]] = $row['volume'];
        }
        return $tmp_array;
    }

}