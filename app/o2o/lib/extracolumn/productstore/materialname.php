<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * o2o 二期
 * 20160810
 * @author wangjianjun@shopex.cn
 * @version 1.0
 */
class o2o_extracolumn_productstore_materialname extends o2o_extracolumn_abstract implements o2o_extracolumn_interface{

    protected $__pkey = 'id';

    protected $__extra_column = 'column_material_name';

    /**
     * 统一获取主键和物料名称之间的关系
     * @param $ids
     */
    public function associatedData($ids){
        //根据主键获取bm_ids
        $mdlO2oProductStore = app::get('o2o')->model('product_store');
        $mdlMaterialBasic = app::get('material')->model('basic_material');
        $rs_info = $mdlO2oProductStore->getList("*",array("id|in"=>$ids));
        $bm_ids = array();
        foreach ($rs_info as $var_info){
            if($var_info["bm_id"] && !in_array($var_info["bm_id"],$bm_ids)){
                $bm_ids[] = $var_info["bm_id"];
            }
        }
        
        //获取bm_id和material_name之间的关系
        $rs_material = $mdlMaterialBasic->getList("bm_id,material_name",array("bm_id|in"=>$bm_ids));
        $rl_bm_id_material_name = array();
        foreach ($rs_material as $var_material){
            $rl_bm_id_material_name[$var_material["bm_id"]] = $var_material["material_name"];
        }
        
        //最终获取主键和material_name之间的关系
        $return_arr = array();
        foreach ($rs_info as $item_info){
            $return_arr[$item_info["id"]] = $rl_bm_id_material_name[$item_info["bm_id"]];
        }
        
        return $return_arr;
    }

}