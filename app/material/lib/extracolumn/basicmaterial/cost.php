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
class material_extracolumn_basicmaterial_cost extends desktop_extracolumn_abstract implements desktop_extracolumn_interface{

    protected $__pkey = 'bm_id';

    protected $__extra_column = 'column_cost';

    /**
     *
     * 获取基础物料字段成本价
     * @param $ids
     * @return array $tmp_array关联数据数组
     */
    public function associatedData($ids){
        //根据发货单ids获取相应的备注信息
        $basicMaterialExtObj = app::get('material')->model('basic_material_ext');
        $cost_lists = $basicMaterialExtObj->getList('cost,'.$this->__pkey,array($this->__pkey => $ids));

        $tmp_array= array();
        foreach($cost_lists as $k=>$row){
            if (!kernel::single('desktop_user')->has_permission('cost_price')) {
                $tmp_array[$row[$this->__pkey]] = '-';
            }else{
                $tmp_array[$row[$this->__pkey]] = $row['cost'];
            }
        }
        return $tmp_array;
    }

}