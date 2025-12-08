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
class o2o_extracolumn_branchproduct_typename extends o2o_extracolumn_abstract implements o2o_extracolumn_interface{

    protected $__pkey = 'id';

    protected $__extra_column = 'column_type_name';

    /**
     * 统一获取主键和type_name分类之间的关系
     * @param $ids
     */
    public function associatedData($ids){
        //根据主键获取bm_ids
        $mdlO2oBranchProduct = app::get('o2o')->model('branch_product');
        $mdlMaterialBasicExt = app::get('material')->model('basic_material_ext');
        $mdlOmeGoodsType = app::get('ome')->model('goods_type');
        $rs_info = $mdlO2oBranchProduct->getList("*",array("id|in"=>$ids));
        $bm_ids = array();
        foreach ($rs_info as $var_info){
            if($var_info["bm_id"] && !in_array($var_info["bm_id"],$bm_ids)){
                $bm_ids[] = $var_info["bm_id"];
            }
        }
        
        //获取bm_id和cat_id之间的关系
        $rs_material = $mdlMaterialBasicExt->getList("bm_id,cat_id",array("bm_id|in"=>$bm_ids));
        $cat_ids = array();
        foreach ($rs_material as $var_material){
            if($var_material["cat_id"] && !in_array($var_material["cat_id"],$cat_ids)){
                $cat_ids[] = $var_material["cat_id"];
            }
        }
        
        //获取cat_id和type_name分类之间的关系
        $rs_type = $mdlOmeGoodsType->getList("type_id,name",array("type_id|in"=>$cat_ids));
        $rl_type_id_type_name = array();
        foreach ($rs_type as $var_type){
            $rl_type_id_type_name[$var_type["type_id"]] = $var_type["name"];
        }
        
        //获取bm_id和type_name分类之间的关系
        $rl_bm_id_type_name = array();
        foreach ($rs_material as $var_material){
            $rl_bm_id_type_name[$var_material["bm_id"]] = $rl_type_id_type_name[$var_material["cat_id"]];
        }
        
        //最终获取主键和type_name分类之间的关系
        $return_arr = array();
        foreach ($rs_info as $item_info){
            $return_arr[$item_info["id"]] = "-";
            if($rl_bm_id_type_name[$item_info["bm_id"]]){
                $return_arr[$item_info["id"]] = $rl_bm_id_type_name[$item_info["bm_id"]];
            }
        }
        
        return $return_arr;
    }

}