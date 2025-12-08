<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class tbo2o_mdl_store extends dbeav_model{

    //同步状态
    function modifier_sync($row){
        switch ($row){
            case "1":
                $sync_text = "未同步";
                break;
            case "2":
                $sync_text = "同步失败";
                break;
            case "3":
                $sync_text = "同步成功";
                break;
        }
        return $sync_text;
    }
    
    //门店类目
    function modifier_cat_id($row){
        $str_cat_name = "";
        $mdltbo2oStoreCat = app::get('tbo2o')->model('store_cat');
        $rs_cat = $mdltbo2oStoreCat->dump(array("cat_id"=>$row),"cat_path");
        if($rs_cat["cat_path"]){
            $arr_cat = explode(",",$rs_cat["cat_path"]);
            $rs_cats = $mdltbo2oStoreCat->getList("cat_id,cat_name",array("cat_id|in"=>$arr_cat));
            $rl_c_id_c_name = array();
            foreach ($rs_cats as $v_c){
                $rl_c_id_c_name[$v_c["cat_id"]] = $v_c["cat_name"];
            }
            foreach ($arr_cat as $a_k => $a_c){
                $t_c_name = $rl_c_id_c_name[$a_c];
                if($a_k > 0){
                    $t_c_name = "/".$t_c_name;
                }
                $str_cat_name .= $t_c_name;
            }
        }
        return $str_cat_name;
    }
    
}