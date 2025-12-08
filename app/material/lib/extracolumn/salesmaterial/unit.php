<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 销售物料字段包装单位
 * @author xiayuanjun@shopex.cn
 * @version 1.0
 */
class material_extracolumn_salesmaterial_unit extends desktop_extracolumn_abstract implements desktop_extracolumn_interface{

    protected $__pkey = 'sm_id';

    protected $__extra_column = 'column_unit';

    /**
     *
     * 获取基础物料字段售价
     * @param $ids
     * @return array $tmp_array关联数据数组
     */
    public function associatedData($ids){
        //根据发货单ids获取相应的备注信息
        $salesMaterialExtObj = app::get('material')->model('sales_material_ext');
        $unit_lists = $salesMaterialExtObj->getList('unit,'.$this->__pkey,array($this->__pkey => $ids));

        $tmp_array= array();
        foreach($unit_lists as $k=>$row){
             $tmp_array[$row[$this->__pkey]] = $row['unit'];
        }
        return $tmp_array;
    }

}