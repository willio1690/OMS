<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 半成品明细信息
 * @author xiayuanjun@shopex.cn
 * @version 1.0
 */
class material_extracolumn_basicmaterial_semimaterial extends desktop_extracolumn_abstract implements desktop_extracolumn_interface{

    protected $__pkey = 'bm_id';

    protected $__extra_column = 'column_semi_material';

    /**
     *
     * 获取成品基础物料ID获取半成品物料信息
     * @param $ids
     * @return array $tmp_array关联数据数组
     */
    public function associatedData($ids){
        $basicMaterialCombinationItemsObj = app::get('material')->model('basic_material_combination_items');
        $seMiBasicMInfos = $basicMaterialCombinationItemsObj->getList('pbm_id,material_name,material_bn,material_num',array('pbm_id'=>$ids), 0, -1);

        $tmp_array= array();
        foreach($seMiBasicMInfos as $k=>$basicMaterial){
            if(isset($tmp_array[$basicMaterial['pbm_id']])){
                $tmp_array[$basicMaterial['pbm_id']] .= "  |  ".$basicMaterial['material_name']."(".$basicMaterial['material_bn'].") x ".$basicMaterial['material_num'];
            }else{
                $tmp_array[$basicMaterial['pbm_id']] = $basicMaterial['material_name']."(".$basicMaterial['material_bn'].") x ".$basicMaterial['material_num'];
            }
        }
        return $tmp_array;
    }

}