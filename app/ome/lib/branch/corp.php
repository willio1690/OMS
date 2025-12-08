<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 仓库与物流公司的关系
 * 20170801 by wangjianjun
 * version 1.0
 */
class ome_branch_corp{

    /*
     * 根据仓库ids获取指定物流公司ids
     * $branch_ids array
     * return array
     */

    public function getCorpIdsByBranchId($branch_ids){
	    $return_arr = array();
	    if (empty($branch_ids)){
	        return $return_arr;
	    }
	    $mdl_ome_branch_corp = app::get("ome")->model("branch_corp");
	    $rs_ome_branch_corp = $mdl_ome_branch_corp->getList("*",array("branch_id"=>$branch_ids));
	    if (!empty($rs_ome_branch_corp)){
	        foreach ($rs_ome_branch_corp as $var_b_c){
	            if (!in_array($var_b_c["corp_id"],$return_arr)){
	                $return_arr[] = $var_b_c["corp_id"];
	            }
            }
        }
        return $return_arr;
	}
	
	/*
	 * 根据物流公司corp_id、all_branch(true/false)、branch_id将摒弃两个旧字段来建立与仓库之间的关系
	 * 传入参数举例：$arr_corp = array("corp_id"=>"1") 和所有仓库建立关系
	 * 或者$arr_corp = array("corp_id"=>"1","branch_id"=>"1") 只和指定仓库建立关系
	 * $branch_ids 电商主仓getlist数组
	 */
    /**
     * 创建BranchCorpRelationship
     * @param mixed $arr_corp arr_corp
     * @param mixed $branch_ids ID
     * @return mixed 返回值
     */
    public function createBranchCorpRelationship($arr_corp,$branch_ids){
	    if (empty($arr_corp) || empty($branch_ids)){
	       return;  
	    }
	    $mdl_ome_branch_corp = app::get("ome")->model("branch_corp");
	    if(isset($arr_corp["branch_id"]) && $arr_corp["branch_id"]){ //指定仓库
	        $insert_do_arr = array(
                "branch_id" => $arr_corp["branch_id"],
                "corp_id" => $arr_corp["corp_id"],
	        );
	        $rs_arr = $mdl_ome_branch_corp->dump($insert_do_arr);
	        if (empty($rs_arr)){ //不存在的新增
	            $mdl_ome_branch_corp->insert($insert_do_arr);
	        }
	    }else{ //所有仓库
	        foreach ($branch_ids as $var_branch_id){
	            $insert_do_arr = array(
	                    "branch_id" => $var_branch_id["branch_id"],
	                    "corp_id" => $arr_corp["corp_id"],
	            );
	            $rs_arr = $mdl_ome_branch_corp->dump($insert_do_arr);
	            if (empty($rs_arr)){ //不存在的新增
	                $mdl_ome_branch_corp->insert($insert_do_arr);
	            }
	        }
	    }
	}

}