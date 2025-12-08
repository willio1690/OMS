<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * column_season
 * @author xiayuanjun@shopex.cn
 * @version 1.0
 */
class material_extracolumn_basicmaterial_widthnm extends desktop_extracolumn_abstract implements desktop_extracolumn_interface{

    protected $__pkey = 'bm_id';
    protected $__extra_column = 'column_widthnm';

    /**
     *
     * 获取基础物料字段重量
     * @param $ids
     * @return array $tmp_array关联数据数组
     */
    public function associatedData($ids){
        //根据发货单ids获取相应的备注信息
        $propsMdl = app::get('material')->model('basic_material_props');
        $props_lists = $propsMdl->getList('props_value,'.$this->__pkey,array($this->__pkey => $ids,'props_col'=>'widthnm'));

        $tmp_array= array();
        foreach($props_lists as $k=>$row){
             $tmp_array[$row[$this->__pkey]] = $row['props_value'];
        }
        return $tmp_array;
    }

}