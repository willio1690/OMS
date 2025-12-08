<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_inventorylist{

    //当类型为期初时，o2o判断门店仓是否有库存记录
    function check_product_iostock($branch_id){
        $mdlO2oProductStore = app::get('ome')->model('branch_product');
        $iostock = $mdlO2oProductStore->dump(array('branch_id'=>$branch_id));
        if(!empty($iostock)){
            return true;
        }else{
            return false;
        }
    }
    
    //判断是否有此门店仓的盘点信息
    function get_inventorybybranch_id($branch_id){
        $inventoryObj = app::get('o2o')->model('inventory');
        $inventory = $inventoryObj->dump(array('branch_id'=>$branch_id),'inventory_id');
        if($inventory){
            return true;
        }else{
            return false;
        }
    }
    
    //盘点单作废处理
    function cancel_inventory($inventory_ids){
        if(empty($inventory_ids)){
            return false;
        }
        $mdlO2oInventory = app::get('o2o')->model('inventory');
        $update_arr = array("status"=>3);
        $filter_arr = array("inventory_id|in"=>$inventory_ids);
        $re = $mdlO2oInventory->update($update_arr,$filter_arr);
        if($re){
            //记录作废日志
            $opObj  = app::get('ome')->model('operation_log');
            foreach ($inventory_ids as $var_id){
                $opObj->write_log('inventory_cancel@o2o', $var_id, '门店盘点单作废成功');
            }
            return true;
        }
    }
    
    //盘点单删除处理
    function delete_inventory($inventory_ids){
        if(empty($inventory_ids)){
            return false;
        }
        $mdlO2oInventory = app::get('o2o')->model('inventory');
        $mdlO2oInventoryItems = app::get('o2o')->model('inventory_items');
        foreach ($inventory_ids as $var_inventory_id){
            $mdlO2oInventory->delete(array('inventory_id'=>$var_inventory_id));
            $mdlO2oInventoryItems->delete(array('inventory_id'=>$var_inventory_id));
        }
        return true;
    }
    
    //获取查看页，确认页，编辑页 统一的基本信息
    function get_basic_info($inventory_id){
        $mdlO2oInventory = app::get('o2o')->model('inventory');
        $rs_inventory = $mdlO2oInventory->dump(array("inventory_id"=>$inventory_id),"*");
        //盘点类型
        $rs_inventory["inventory_type_text"] = $mdlO2oInventory->get_inventory_type($rs_inventory["inventory_type"],"key");
        //申请人和盘点人
        $rs_inventory["op_name"] = "-";
        $rs_inventory["confirm_op_name"] = "-";
        $op_ids = array();
        if($rs_inventory["op_id"]){
            $op_ids[] = $rs_inventory["op_id"];
        }
        if($rs_inventory["confirm_op_id"] && !empty($op_ids) && !in_array($rs_inventory["confirm_op_id"], $op_ids)){
            $op_ids[] = $rs_inventory["confirm_op_id"];
        }
        if(!empty($op_ids)){
            $mdlDesktopUsers = app::get('desktop')->model('users');
            $rs_users = $mdlDesktopUsers->getList("user_id,name",array("user_id|in"=>$op_ids));
            if (!empty($rs_users)){
                $rl_op_id_op_name = array();
                foreach ($rs_users as $var_user){
                    $rl_op_id_op_name[$var_user["user_id"]] = $var_user["name"];
                }
                if($rs_inventory["op_id"]){
                    $rs_inventory["op_name"] = $rl_op_id_op_name[$rs_inventory["op_id"]];
                }
                if($rs_inventory["confirm_op_id"]){
                    $rs_inventory["confirm_op_name"] = $rl_op_id_op_name[$rs_inventory["confirm_op_id"]];
                }
            }
        }
        //门店
        $mdlOmeBranch = app::get('ome')->model('branch');
        $rs_branch = $mdlOmeBranch->dump(array("branch_id"=>$rs_inventory["branch_id"]),"name");
        $rs_inventory["store_name"] = $rs_branch["name"];
        //申请时间
        if($rs_inventory["createtime"]){
            $rs_inventory["createtime_format"] = date("Y-m-d H:i:s",$rs_inventory["createtime"]);
        }
        //盘点时间
        $rs_inventory["confirm_time_format"] = "-";
        if($rs_inventory["confirm_time"]){
            $rs_inventory["confirm_time_format"] = date("Y-m-d H:i:s",$rs_inventory["confirm_time"]);
        }
        return $rs_inventory;
    }
    
    //获取查看页，确认页，编辑页 统一的基本信息
    function get_inventory_item_list($filter=null,$offset=0,$limit=-1,$orderby=null){
        $mdlO2oInventoryItems = app::get('o2o')->model('inventory_items');
        $rs_items = $mdlO2oInventoryItems->getList("*",$filter,$offset,$limit,$orderby);
        //这里统一获取bm_ids
        $bm_ids = array();
        foreach ($rs_items as $var_item){
            if($var_item["bm_id"] && !in_array($var_item["bm_id"],$bm_ids)){
                $bm_ids[] = $var_item["bm_id"];
            }
        }
        //获取bm_id和material_bn/material_name
        $mdlMaterialBasic = app::get('material')->model('basic_material');
        $rs_material_basic = $mdlMaterialBasic->getList("bm_id,material_bn,material_name",array("bm_id|in"=>$bm_ids));
        $rl_bm_id_material_info = array();
        foreach ($rs_material_basic as $var_material_basic){
            $rl_bm_id_material_info[$var_material_basic["bm_id"]] = array(
                "material_bn" => $var_material_basic["material_bn"],
                "material_name" => $var_material_basic["material_name"],
            );
        }
        //获取bm_id和spec_info规格
        $mdlMaterialBasicExt = app::get('material')->model('basic_material_ext');
        $rs_material_ext = $mdlMaterialBasicExt->getList("bm_id,specifications",array("bm_id|in"=>$bm_ids));
        $rl_bm_id_spec_info = array();
        foreach ($rs_material_ext as $var_material_ext){
            $rl_bm_id_spec_info[$var_material_ext["bm_id"]] = $var_material_ext["specifications"];
        }
        //最终压入material_bn/material_name/spec_info
        foreach ($rs_items as &$var_f){
            $var_f["material_bn"] = $rl_bm_id_material_info[$var_f["bm_id"]]["material_bn"];
            $var_f["material_name"] = $rl_bm_id_material_info[$var_f["bm_id"]]["material_name"];
            $var_f["spec_info"] = "-";
            if($rl_bm_id_spec_info[$var_f["bm_id"]]){
                $var_f["spec_info"] = $rl_bm_id_spec_info[$var_f["bm_id"]];
            }
        }
        unset($var_f);
        
        return $rs_items;
    }
    
    //获取总记录条数
    function get_inventory_count($filter){
        $mdlO2oInventoryItems = app::get('o2o')->model('inventory_items');
        $count = $mdlO2oInventoryItems->count($filter);
        return $count;
    }
    
}
