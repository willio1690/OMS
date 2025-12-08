<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 人工库存预占导入队列执行
 * by wangjianjun 20171115
 */
class console_stock_artificial_import{
    
    function run(&$cursor_id,$params){
        if(empty($params["sdfdata"])){
            return false;   
        }
        //开启事务，确保所有数据库操作的原子性
        $trans = kernel::database()->beginTransaction();
        $csaf_lib = kernel::single('console_stock_artificial_freeze');
        $rl_branch_id_info = array();
        $mdl_ome_operation_log = app::get('ome')->model('operation_log');
        foreach($params["sdfdata"] as $var_sdf){
            $last_bmsaf_id= $csaf_lib->insert_freeze_data($var_sdf);
            $mdl_ome_operation_log->write_log('import_artificial_freeze@ome',$last_bmsaf_id,"导入人工库存预占记录");
            $current_data_arr = array(
                "branch_id" => $var_sdf["branch_id"],
                "bm_id" => $var_sdf["bm_id"],
                "freeze_num" => $var_sdf["freeze_num"],
                "reason" => $var_sdf["reason"],
                "bn" => $var_sdf["bn"],
            );
            $rl_branch_id_info[$var_sdf["branch_id"]][] = array_merge(array("obj_id"=>$last_bmsaf_id),$current_data_arr);
        }
        //库存管控
        $storeManageLib = kernel::single('ome_store_manage');
        $params['node_type'] = "artificialFreeze";
        foreach($rl_branch_id_info as $key_branch_id => $var_bii){
            $storeManageLib->loadBranch(array('branch_id'=>$key_branch_id));
            $params['params'] = $var_bii;
            $processResult = $storeManageLib->processBranchStore($params,$err_msg);
            if(!$processResult){ //库存管控失败 $err_msg
                kernel::database()->rollBack();
                return false;
            }
        }
        //所有操作成功，提交事务
        kernel::database()->commit($trans);
        return false;
    }
    
}