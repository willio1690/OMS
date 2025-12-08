<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 门店供货管理，物料关联，可选物料list
 * Class o2o_mdl_branch_productinfo
 */
class o2o_mdl_branch_productinfo extends dbeav_model{
    
    /**
     * count
     * @param mixed $filter filter
     * @return mixed 返回值
     */

    public function count($filter = null)
    {
        $mdlMaterialBasic = app::get('material')->model('basic_material');
        $sql = "SELECT count(*)as _count FROM sdb_material_basic_material as mbm";
        $sql_filter = $this->get_filter($filter);
        $sql = $sql.$sql_filter;
        $rs_material = $mdlMaterialBasic->db->select($sql);
        return $rs_material[0]['_count'];
    }
    
    public function getList($cols = '*', $params = array(), $offset = 0, $limit = -1, $orderType = null){

        $mdlMaterialBasic = app::get('material')->model('basic_material');
        $mdlMaterialBasicExt = app::get('material')->model('basic_material_ext');
        $mdlOmeBrand = app::get('ome')->model('brand');
        $mdlOmeGoodsType = app::get('ome')->model('goods_type');

        $sql = "SELECT mbm.bm_id,mbm.material_name,mbm.material_bn FROM sdb_material_basic_material as mbm";
        $sql_filter = $this->get_filter($params);
        if($limit){
            $sql = $sql.$sql_filter." limit ". $offset .",". $limit;
        }else{
            $sql = $sql.$sql_filter;
        }

        $rs_material = $mdlMaterialBasic->db->select($sql);

        if(empty($rs_material)){
            return array();
        }

        $bm_ids = array();
        foreach ($rs_material as $var_material){
            $bm_ids[] = $var_material["bm_id"];
        }

        $rs_material_ext = $mdlMaterialBasicExt->getList("bm_id,specifications,brand_id,cat_id",array("bm_id|in"=>$bm_ids));
        $brand_ids = array();
        $cat_ids = array();
        foreach ($rs_material_ext as $var_material_ext){
            if($var_material_ext["brand_id"] && !in_array($var_material_ext["brand_id"],$brand_ids)){
                $brand_ids[] = $var_material_ext["brand_id"];
            }
            if($var_material_ext["cat_id"] && !in_array($var_material_ext["cat_id"],$cat_ids)){
                $cat_ids[] = $var_material_ext["cat_id"];
            }
        }

        //获取品牌
        if($brand_ids){
            $rs_brand = $mdlOmeBrand->getList("brand_id,brand_name",array("brand_id|in"=>$brand_ids));
            $rl_brand_id_name = array();
            foreach ($rs_brand as $var_brand){
                $rl_brand_id_name[$var_brand["brand_id"]] = $var_brand["brand_name"];
            }
        }

        //获取类型
        if($cat_ids){
            $rs_cat = $mdlOmeGoodsType->getList("type_id,name",array("type_id|in"=>$cat_ids));
            $rl_type_id_name = array();
            foreach ($rs_cat as $var_cat){
                $rl_type_id_name[$var_cat["type_id"]] = $var_cat["name"];
            }
        }

        //获取bm_id和规格、品牌、类型
        $rl_bm_id_info = array();
        foreach ($rs_material_ext as $item_material_ext){
            $rl_bm_id_info[$item_material_ext["bm_id"]] = array(
                "specifications" => $item_material_ext["specifications"],
                "brand_name" => $rl_brand_id_name[$item_material_ext["brand_id"]],
                "type_name" => $rl_type_id_name[$item_material_ext["cat_id"]],
            );
        }

        foreach ($rs_material as &$item_material){
            $item_material["specifications"] = "-";
            $item_material["brand_name"] = "-";
            $item_material["type_name"] = "-";
            if($rl_bm_id_info[$item_material["bm_id"]]["specifications"]){
                $item_material["specifications"] = $rl_bm_id_info[$item_material["bm_id"]]["specifications"];
            }
            if($rl_bm_id_info[$item_material["bm_id"]]["brand_name"]){
                $item_material["brand_name"] = $rl_bm_id_info[$item_material["bm_id"]]["brand_name"];
            }
            if($rl_bm_id_info[$item_material["bm_id"]]["type_name"]){
                $item_material["type_name"] = $rl_bm_id_info[$item_material["bm_id"]]["type_name"];
            }
        }
        unset($item_material);

        return $rs_material;
    }

    private function get_filter($params=array()){
        $sql_filter = " where mbm.visibled=1";
        if(!empty($params)){
            if (isset($params['material_name'])) {
                $sql_filter = $sql_filter." and mbm.material_name like '".$params['material_name']."%'";
            }
            if (isset($params['material_bn'])) {
                $sql_filter = $sql_filter." and mbm.material_bn like '".$params['material_bn']."%'";
            }
            if (isset($params['brand_name'])) {
                $sql_join = " left join sdb_material_basic_material_ext as mbme on mbm.bm_id=mbme.bm_id";
                $sql_filter = $sql_join.$sql_filter." and mbme.brand_id=".intval($params['brand_name']);
            }
            if (isset($params['type_name'])) {
                $sql_join = " left join sdb_material_basic_material_ext as mbme on mbm.bm_id=mbme.bm_id";
                $sql_filter = $sql_join.$sql_filter." and mbme.cat_id=".intval($params['type_name']);
            }
        }
        return $sql_filter;
    }
    
    function searchOptions(){
        $parentOptions = parent::searchOptions();
        $childOptions = array(
            'material_name'=>app::get('ome')->_('物料名称'),
            'material_bn'=>app::get('ome')->_('物料编码'),
            'brand_name'=>app::get('ome')->_('品牌'),
            'type_name'     =>app::get('ome')->_('分类'),
        );
        
        return $Options = array_merge($parentOptions,$childOptions);
    }
    
    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema(){
        
        $schema = array (
            'columns' => array (
                'bm_id'=>array(
                    'type' => 'number',
                    'required' => true,
                    'pkey' => true,
                    'extra' => 'auto_increment',
                    'editable' => false,
                    'deny_export' => true,
                ),
                'material_name' =>
                    array (
                        'label' => '基础物料名称',
                        'width' => 120,
                        'order' => 1,
                    ),
                'material_bn'=>array(
                    'label' => '基础物料编码',
                    'width' => '70'
                ),
                'brand_name' =>
                    array(
                        'label' => '品牌',
                        'width' => 120,
                    ),
                'type_name' =>
                    array(
                        'label' => '分类',
                        'width' => 120,
                    ),
                'specifications' =>
                    array(
                        'label' => '规格',
                        'width' => 130,
                    )
            ),
            'idColumn' => 'bm_id',
            'in_list' => array(
                0 => 'material_name',
                1 => 'material_bn',
                2 => 'brand_name',
                3 => 'type_name',
                4 => 'specifications',
            ),
            'default_in_list' => array(
                0 => 'material_name',
                1 => 'material_bn',
                2 => 'brand_name',
                3 => 'type_name',
                4 => 'specifications'
            ),
        );
        return $schema;
    }
}