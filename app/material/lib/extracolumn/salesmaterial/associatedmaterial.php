<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 销售物料关联的基础物料信息
 * @author xiayuanjun@shopex.cn
 * @version 1.0
 */
class material_extracolumn_salesmaterial_associatedmaterial extends desktop_extracolumn_abstract implements desktop_extracolumn_interface{

    protected $__pkey = 'sm_id';

    protected $__extra_column = 'column_associated_material';

    /**
     *
     * 获取销售物料关联的基础物料信息
     * @param $ids
     * @return array $tmp_array关联数据数组
     */
    public function associatedData($ids){
        //获取福袋、多选一的sm_id数组 移除$ids中这两个类型的值
        $return_arr = $this->get_additional_types_sm_ids($ids);
        if(!empty($return_arr)){
            if(!empty($return_arr["luckybag"])){
                $arr_luckybag_sm_ids = $return_arr["luckybag"];
            }
            if(!empty($return_arr["pickone"])){
                $arr_pickone_sm_ids = $return_arr["pickone"];
            }
        }
        
        //普通、促销和赠品
        $salesBasicMaterialLib = kernel::single('material_sales_material');
        $salesBasicMaterialLists = $salesBasicMaterialLib->getBasicMBySalesMIds($ids);
        $tmp_array= array();
        foreach($salesBasicMaterialLists as $sm_id=>$basicMaterials){
            foreach($basicMaterials as $k =>$basicMaterial){
                if(isset($tmp_array[$sm_id])){
                    $tmp_array[$sm_id] .= "  |  ".$basicMaterial['material_name']."(".$basicMaterial['material_bn'].") x ".$basicMaterial['number'];
                }else{
                    $tmp_array[$sm_id] = $basicMaterial['material_name']."(".$basicMaterial['material_bn'].") x ".$basicMaterial['number'];
                }
            }
        }
        
        //获取福袋类型数据
        if(!empty($arr_luckybag_sm_ids)){
            $mdl_ma_lu_ru = app::get('material')->model('luckybag_rules');
            $rs_luckybag = $mdl_ma_lu_ru->getList("*",array("sm_id"=>$arr_luckybag_sm_ids));
            $rl_sm_id_lbr = array();
            $rl_sm_id_bm_id = array();
            $rl_sm_id_lbr_info = array();
            foreach($rs_luckybag as $var_lkb){
                $rl_sm_id_lbr[$var_lkb["sm_id"]][] = $var_lkb["lbr_id"];
                $rl_sm_id_bm_id[$var_lkb["sm_id"]][] = $var_lkb["bm_ids"];
                $rl_sm_id_lbr_info[$var_lkb["sm_id"]][] = $var_lkb;
            }
            foreach($arr_luckybag_sm_ids as $var_lsi){
                $lbr_count = count($rl_sm_id_lbr[$var_lsi]);
                $bm_id_count = 0;
                foreach($rl_sm_id_bm_id[$var_lsi] as $var_sibi){
                    $temp_sibi = explode(",", $var_sibi);
                    $bm_id_count = $bm_id_count + count($temp_sibi);
                }
                $luckybag_dec = $this->get_luckybag_dec($rl_sm_id_lbr_info[$var_lsi]);
                $tmp_array[$var_lsi] = '<div onmouseover="bindFinderColTip(event);" rel="'.$luckybag_dec.'">'.$lbr_count.'组/'.$bm_id_count.'个SKU随机送</div>';
            }
        }
        
        //获取多选一类型数据
        if(!empty($arr_pickone_sm_ids)){
            $this->get_pickone_show($arr_pickone_sm_ids,$tmp_array);
        }
        
        return $tmp_array;
    }
    
    //获取福袋、多选一的sm_id数组
    private function get_additional_types_sm_ids(&$ids){
        //获取福袋类型4、多选一5的销售物料ids
        $mdl_ma_sa_ma = app::get('material')->model('sales_material');
        $rs_info = $mdl_ma_sa_ma->getList("*",array("sm_id"=>$ids,"sales_material_type"=>array("4","5")));
        $return_arr = array();
        if(!empty($rs_info)){
            $used_sm_ids_arr = array();
            foreach($rs_info as $var_info){
                if($var_info["sales_material_type"] == "4"){ //福袋
                    $return_arr["luckybag"][] = $var_info["sm_id"];
                }elseif($var_info["sales_material_type"] == "5"){ //多选一
                    $return_arr["pickone"][] =  $var_info["sm_id"];
                }
                $used_sm_ids_arr[] = $var_info["sm_id"]; //福袋、多选一sm_id总数组
            }
            foreach($ids as $key_sm_id => $var_sm_id){
                if(in_array($var_sm_id,$used_sm_ids_arr)){
                    unset($ids[$key_sm_id]);
                }
            }
        }
        return $return_arr;
    }
    
    private function get_luckybag_dec($lbr_info){
        $mdl_ma_ba_ma = app::get('material')->model('basic_material');
        $luckybag_dec = "<div style='text-align:left'>";
        $count_rule = count($lbr_info);
        $count = 1;
        foreach($lbr_info as $var_li){
            $luckybag_dec .= "<b>".$var_li["lbr_name"]."</b><br />";
            $bm_ids = explode(",",$var_li["bm_ids"]);
            $rs_bm_info = $mdl_ma_ba_ma->getList("material_name,material_bn",array("bm_id"=>$bm_ids));
            $bm_infos = array();
            foreach ($rs_bm_info as $var_bif){
                $bm_infos[] = $var_bif["material_name"]."(".$var_bif["material_bn"].")";
            }
            $luckybag_dec .= implode("<br />",$bm_infos)."<br />随机选".$var_li["sku_num"]."个sku，分别发".$var_li["send_num"]."件";
            if ($count < $count_rule){
                $luckybag_dec .= "<br /><br />";
            }
            $count++;
        }
        $luckybag_dec .= "</div>";
        return $luckybag_dec;
    }
    
    //获取多选一
    private function get_pickone_show($arr_pickone_sm_ids,&$tmp_array){
        $mdl_ma_pickone_rules = app::get('material')->model('pickone_rules');
        $mdl_ma_ba_ma = app::get('material')->model('basic_material');
        $rs_pickone = $mdl_ma_pickone_rules->getList("*",array("sm_id"=>$arr_pickone_sm_ids));
        $rl_arr_select_type = array("1"=>"随机","2"=>"排序");
        $rl_sm_id_select_type = array();
        $rl_sm_id_bm_ids = array();
        $rl_sm_id_bm_id_sort = array();
        $total_bm_ids = array();
        foreach($rs_pickone as $var_pickone){
            if(!isset($rl_sm_id_select_type[$var_pickone["sm_id"]])){ //一个sm_id 只有一个选择类型 做一次即可
                $rl_sm_id_select_type[$var_pickone["sm_id"]] = $rl_arr_select_type[$var_pickone["select_type"]];
            }
            $rl_sm_id_bm_ids[$var_pickone["sm_id"]][] = $var_pickone["bm_id"];
            $total_bm_ids[] = $var_pickone["bm_id"];
            $rl_sm_id_bm_id_sort[$var_pickone["sm_id"]."_".$var_pickone["bm_id"]] = $var_pickone["sort"];
        }
        $rs_basic_material = $mdl_ma_ba_ma->getList("bm_id,material_name,material_bn",array("bm_id"=>$total_bm_ids));
        $rl_bm_id_bm_info = array();
        foreach($rs_basic_material as $var_bm){
            $rl_bm_id_bm_info[$var_bm["bm_id"]] = $var_bm;
        }
        foreach($arr_pickone_sm_ids as $var_sm_id){
            $bm_infos = array();
            foreach ($rl_sm_id_bm_ids[$var_sm_id] as $var_bm_id){
                $bm_infos[] = $rl_bm_id_bm_info[$var_bm_id]["material_name"]."(".$rl_bm_id_bm_info[$var_bm_id]["material_bn"].") 排序值：".$rl_sm_id_bm_id_sort[$var_sm_id."_".$var_bm_id];
            }
            $pickone_dec = "<div style='text-align:left'>".implode("<br />",$bm_infos)."</div>";
            $tmp_array[$var_sm_id] = '<div onmouseover="bindFinderColTip(event);" rel="'.$pickone_dec.'">'.count($rl_sm_id_bm_ids[$var_sm_id]).'个SKU按'.$rl_sm_id_select_type[$var_sm_id].'选</div>';
        }
    }

}