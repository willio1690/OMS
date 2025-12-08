<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 门店店铺商品mdl类
 *
 * @author wangbiao<wangbiao@shopex.cn>
 */
class console_mdl_product_store extends dbeav_model
{
    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */

    public function table_name($real = false)
    {
        if($real){
           $table_name = 'sdb_o2o_product_store';
        }else{
           $table_name = 'product_store';
        }
        
        return $table_name;
    }
    
    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema(){
        return app::get('o2o')->model('product_store')->get_schema();
    }
    
    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter, $tableAlias=null, $baseWhere=null)
    {
        $where = array(1);
        
        //MIN最小库存
        $min_store = trim($_POST['min_store']);
        if(isset($_POST['min_store']) && $min_store != ''){
            $min_store = intval($min_store);
            
            if($min_store == 0 && $_POST['min_store_search'] == 'lthan'){
                $where[] = " min_store =0";
            }else{
                if($_POST['min_store_search'] == 'nequal'){
                    $where[] = " min_store = ". $min_store;
                }else if($_POST['min_store_search'] == 'than'){
                    $where[] = " min_store >". $min_store;
                }else if($_POST['min_store_search']=='lthan'){
                    $where[] = " min_store <". $min_store;
                }
            }
        }
        unset($filter['min_store_search'], $filter['min_store']);
        
        //MAX最大库存
        $max_store = trim($_POST['max_store']);
        if(isset($_POST['max_store']) && $max_store != ''){
            $max_store = intval($max_store);
            
            if($max_store == 0 && $_POST['max_store_search'] == 'lthan'){
                $where[] = " max_store =0";
            }else{
                if($_POST['max_store_search'] == 'nequal'){
                    $where[] = " max_store = ". $max_store;
                }else if($_POST['max_store_search'] == 'than'){
                    $where[] = " max_store >". $max_store;
                }else if($_POST['max_store_search']=='lthan'){
                    $where[] = " max_store <". $max_store;
                }
            }
        }
        unset($filter['max_store_search'], $filter['max_store']);
        
        //异常库存条件
        if($filter['abnormal_store']){
            if(empty($min_store) && $min_store !== 0){
                $where[] = " min_store >0";
            }
            
            if(empty($max_store) && $max_store !== 0){
                $where[] = " max_store >0";
            }
            
            $where[] = " (store < min_store OR store > max_store)";
        }
        unset($filter['abnormal_store']);
        
        return parent::_filter($filter, $tableAlias, $baseWhere)." AND ".implode($where,' AND ');
    }
    
    //扩展字段先定义
    function extra_cols(){
        return array(
                'column_store_name' => array('label'=>'关联门店','width'=>'150','func_suffix'=>'store_name',"order"=>"7"),
                'column_specifications' => array('label'=>'规格','width'=>'120','func_suffix'=>'specifications',"order"=>"6"),
                'column_type_name' => array('label'=>'分类','width'=>'100','func_suffix'=>'type_name',"order"=>"5"),
                'column_brand_name' => array('label'=>'品牌','width'=>'120','func_suffix'=>'brand_name',"order"=>"4"),
                'column_material_bn' => array('label'=>'基础物料编码','width'=>'120','func_suffix'=>'material_bn',"order"=>"3"),
                'column_material_name' => array('label'=>'基础物料名称','width'=>'260','func_suffix'=>'material_name',"order"=>"2"),
        );
    }
    
    function extra_store_name($rows){
        return kernel::single('o2o_extracolumn_productstore_storename')->process($rows);
    }
    
    function extra_material_name($rows){
        return kernel::single('o2o_extracolumn_productstore_materialname')->process($rows);
    }
    
    function extra_material_bn($rows){
        return kernel::single('o2o_extracolumn_productstore_materialbn')->process($rows);
    }
    
    function extra_specifications($rows){
        return kernel::single('o2o_extracolumn_productstore_specifications')->process($rows);
    }
    
    function extra_brand_name($rows){
        return kernel::single('o2o_extracolumn_productstore_brandname')->process($rows);
    }
    
    function extra_type_name($rows){
        return kernel::single('o2o_extracolumn_productstore_typename')->process($rows);
    }
}
?>
