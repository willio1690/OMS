<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 码库基础物料名称字段
 * @author xiayuanjun@shopex.cn
 * @version 1.0
 */
class material_extracolumn_codebase_materialname extends desktop_extracolumn_abstract implements desktop_extracolumn_interface{

    protected $__pkey = 'bm_id';

    protected $__extra_column = 'column_material_name';

    /**
     *
     * 获取基础物料名称字段
     * @param $ids
     * @return array $tmp_array关联数据数组
     */
    public function associatedData($ids){
        //根据物料ids获取相应的物料名称信息
        $basicMaterialObj = app::get('material')->model('basic_material');
        $name_lists = $basicMaterialObj->getList('material_name,'.$this->__pkey,array($this->__pkey => $ids));

        $tmp_array= array();
        foreach($name_lists as $k=>$row){
             $tmp_array[$row[$this->__pkey]] = $row['material_name'];
        }
        return $tmp_array;
    }

}