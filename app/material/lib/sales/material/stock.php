<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 销售物料库存Lib类
 * @author xiayuanjun@shopex.cn
 * @version 1.0
 */
class material_sales_material_stock{

    function __construct(){
        $this->_salesMaterialObj = app::get('material')->model('sales_material');
        $this->_salesBasicMaterialObj = app::get('material')->model('sales_basic_material');
        $this->_basicMaterialStockObj = app::get('material')->model('basic_material_stock');
        $this->_basicMStockFreezeLib  = kernel::single('material_basic_material_stock_freeze');
    }

    /**
     * 
     * 根据销售物料绑定基础物料计算可用库存
     * @param Int $sm_id 销售物料id
     * @return Int $bm_bind_abled_store 可用库存
     */

    public function getSalesMStockById($sm_id){
        $salesMaterialInfo = $this->_salesMaterialObj->getList('sm_id,shop_id,is_bind,sales_material_type',array('sm_id'=>$sm_id), 0, 1);
        if($salesMaterialInfo){
            //已绑定的才有映射关系有库存
            if($salesMaterialInfo[0]['is_bind'] == 1){
                if($salesMaterialInfo[0]["sales_material_type"] == 5){ //多选一
                    return $this->get_pickone_stock_by_sm_id($sm_id);
                }elseif($salesMaterialInfo[0]['sales_material_type'] == 7){
                    //福袋组合
                    $luckybagLib = kernel::single('material_luckybag');
                    $error_msg = '';
                    $stock_num = $luckybagLib->getStockBySmid($sm_id, $error_msg);
                    if(!$stock_num){
                        return 0;
                    }
                    
                    return $stock_num;
                }else{
                    $salesBasicBindInfo = $this->_salesBasicMaterialObj->getList('sm_id,bm_id,number',array('sm_id'=>$sm_id), 0, -1);
                    foreach($salesBasicBindInfo as $k => $bindInfo){
                        $bm_ids[] = $bindInfo['bm_id'];
                        $bm_combine_items[$bindInfo['bm_id']] = $bindInfo['number'];
                    }
                    $bmStoreInfo = $this->_basicMaterialStockObj->getList('bm_id,store,store_freeze',array('bm_id'=>$bm_ids));
                    foreach($bmStoreInfo as $k => $storeInfo){
                        //根据基础物料ID获取对应的冻结库存
                        $storeInfo['store_freeze']    = $this->_basicMStockFreezeLib->getMaterialStockFreeze($storeInfo['bm_id']);
                        $tmp_abled_store =  ($storeInfo['store']-$storeInfo['store_freeze']) > 0 ? ($storeInfo['store']-$storeInfo['store_freeze']) : 0;
                        $bm_bind_abled_store[] = $tmp_abled_store > 0 ? floor($tmp_abled_store/$bm_combine_items[$storeInfo['bm_id']]) : 0;
                    }
                    //升序排列
                    sort($bm_bind_abled_store);
                    return $bm_bind_abled_store[0];
                }
            }
        }
        return 0;
    }
    
    //福袋：根据sm_id获取可用库存数量
    /**
     * 获取_luckybag_stock_by_sm_id
     * @param mixed $sm_id ID
     * @return mixed 返回结果
     */
    public function get_luckybag_stock_by_sm_id($sm_id){
        $mdl_ma_lu_ru = app::get('material')->model('luckybag_rules');
        $rs_ma_lu_ru = $mdl_ma_lu_ru->getList("*",array("sm_id"=>$sm_id));
        $has_luckybag_stock_arr = array();
        //以福袋组合为维度获取当前组合的库存数量
        foreach($rs_ma_lu_ru as $var_mlr){
            $current_bm_ids = explode(",", $var_mlr["bm_ids"]);
            $current_stock_info = $this->_basicMaterialStockObj->getList('bm_id,store,store_freeze',array('bm_id'=>$current_bm_ids));
            //获取bm_id对应的可用库存
            $current_real_store = array();
            foreach($current_stock_info as $var_csi){
                $var_csi['store_freeze'] = $this->_basicMStockFreezeLib->getMaterialStockFreeze($var_csi['bm_id']);
                $tmp_abled_store = ($var_csi['store']-$var_csi['store_freeze']) > 0 ? ($var_csi['store']-$var_csi['store_freeze']) : 0;
                $current_real_store[] = $tmp_abled_store > 0 ? floor($tmp_abled_store/$var_mlr["send_num"]) : 0;
            }
            rsort($current_real_store); //倒叙排库存
            $has_store_arr = array();
            foreach($current_real_store as $var_crs){
                if($var_crs > 0){
                    $has_store_arr[] = $var_crs;
                }else{
                    break;
                }
            }
            //如果根据send_num获取的可用库存大于等于sku_num组最终的luckybag库存数组
            if(count($has_store_arr) >= $var_mlr["sku_num"]){
                $has_luckybag_stock_arr[] = $has_store_arr[0]; //取最大的库存 也就是第一个
            }else{
                $has_luckybag_stock_arr = array();break;
            }
        }
        //只要其中有一组组合根据条件获取可用库存为0 直接返回0
        if(empty($has_luckybag_stock_arr)){
            return 0;
        }else{ //福袋组合之间取最小的库存
            sort($has_luckybag_stock_arr);
            return $has_luckybag_stock_arr[0];
        }
    }
    
    //多选一：根据sm_id获取可用库存数量
    /**
     * 获取_pickone_stock_by_sm_id
     * @param mixed $sm_id ID
     * @return mixed 返回结果
     */
    public function get_pickone_stock_by_sm_id($sm_id){
        $mdl_ma_pickone_ru = app::get('material')->model('pickone_rules');
        $rs_ma_pickone_ru = $mdl_ma_pickone_ru->getList("*",array("sm_id"=>$sm_id));
        $bm_ids = array();
        foreach($rs_ma_pickone_ru as $var_mpr){
            $bm_ids[] = $var_mpr["bm_id"];
        }
        $bm_stock_info = $this->_basicMaterialStockObj->getList('bm_id,store,store_freeze',array('bm_id'=>$bm_ids));
        $store = 0; //多选一的库存：基础物料库存累加
        foreach($bm_stock_info as $var_bsi){
            $current_bm_abled_store = $var_bsi['store']-$var_bsi['store_freeze'];
            $current_bm_abled_store = ($current_bm_abled_store > 0) ? $current_bm_abled_store : 0;
            $store += $current_bm_abled_store;
        }
        return $store;
    }

}