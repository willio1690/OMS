<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_data_original_iostock{

    /**
     * 获取List
     * @param mixed $start_time start_time
     * @param mixed $end_time end_time
     * @param mixed $iostock_bn iostock_bn
     * @param mixed $original_bn original_bn
     * @param mixed $branch_bn branch_bn
     * @param mixed $bn bn
     * @param mixed $type type
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @return mixed 返回结果
     */
    public function getList($start_time,$end_time,$iostock_bn='',$original_bn='',$branch_bn='',$bn='',$type='',$offset=0,$limit=100){
        if(empty($start_time) || empty($end_time)){
            return false;
        }

        $basicMaterialObj = app::get('material')->model('basic_material');
        $basicMaterialBarcode    = kernel::single('material_basic_material_barcode');#基础物料条形码
        
        $shopObj = app::get('ome')->model('shop');
        $branchObj = app::get('ome')->model('branch');
        $iostocktypeObj = app::get('ome')->model('iostock_type');
        $memberObj = app::get('ome')->model('members');
        
        $opObj = app::get('desktop')->model('users');
        $countSql = "select count(iostock_id) as _count from sdb_ome_iostock where ";
        $where = "create_time >=".$start_time." and create_time <".$end_time;
        
        if($iostock_bn != ''){
            $where .= " AND iostock_bn = '".$iostock_bn."'";
        }
        if($original_bn != ''){
            $where .= " AND original_bn = '".$original_bn."'";
        }
        if($branch_bn != ''){
            $_branch = $branchObj->getlist('branch_id',array('branch_bn'=>$branch_bn),0,1);
            $where .= " AND branch_id = '".$_branch[0]['branch_id']."'";
        }
        if($bn != ''){
            $where .= " AND bn = '".$bn."'";
        }
        if($type != ''){
            // $_type = $iostocktypeObj->getlist('type_id',array('type_name'=>$type),0,1);
            // $where .= " AND type_id = '".$_type[0]['type_id']."'";
            $where .= " AND type_id = '" . $type . "'";
        }

        $countList = kernel::database()->selectrow($countSql.$where);

        if(intval($countList['_count']) >0){

            $iostocktypeInfos = array();
            $iostocktype_arr = $iostocktypeObj->getList('type_id,type_name', array(), 0, -1);
            foreach ($iostocktype_arr as $k => $iostocktype){
                $iostocktypeInfos[$iostocktype['type_id']] = $iostocktype['type_name'];
            }

            $branchInfos = array();
            $branch_arr = $branchObj->getList('branch_id,branch_bn,name', array(), 0, -1);
            foreach ($branch_arr as $k => $branch){
                $branchInfos[$branch['branch_id']] = array('branch_bn'=>$branch['branch_bn'],'name'=>$branch['name']);
            }

            $listSql = "select * from sdb_ome_iostock where ";
            $lists = kernel::database()->select($listSql.$where." order by create_time asc limit ".$offset.",".$limit."");

            $arr_original_ids = array();
            $arr_original_bn = array();//原发货单号
            $arr_appropriation_type_ids = array("4","40");
            foreach (kernel::single("ome_iostock")->get_iostock_types() as $typeKey => $typeValue) {
                if (isset($typeValue['is_new'])) {
                    $arr_appropriation_type_ids[] = $typeKey;
                }
            }

            foreach ($lists as $var_list){
                if(in_array($var_list["type_id"],$arr_appropriation_type_ids)){
                    $arr_original_ids[] = $var_list["original_id"];
                }
                if($var_list["type_id"] == 1){
                    $arr_original_bn[] = $var_list['original_bn'];
                }
            }
            if(!empty($arr_original_ids)){
                $taoguaniostockorder_iso_obj = app::get('taoguaniostockorder')->model('iso');
                $taoguaniostockorder_infos = $taoguaniostockorder_iso_obj->getList("iso_id,appropriation_no,name,arrival_no",array('iso_id|in'=>$arr_original_ids));
                $rl_original_id_appropriation_no = array();
                foreach ($taoguaniostockorder_infos as $var_taoguaniostockorder_info){
                    $rl_original_id_appropriation_no[$var_taoguaniostockorder_info["iso_id"]] = $var_taoguaniostockorder_info;
                }
            }

            //获取采购单的退货单号特殊处理方式
            $originalBnNums = array();
            if($arr_original_bn){
                $ios_sql = "select it.bn,it.nums,i.original_bn,i.iso_id,i.arrival_no,i.iso_bn,it.normal_num 
                from sdb_taoguaniostockorder_iso_items it 
                left join sdb_taoguaniostockorder_iso i on it.iso_id = i.iso_id 
                where i.type_id = 1   
                ";
                $ios_sql = $ios_sql." and i.original_bn in ('". implode("','", array_unique($arr_original_bn)) ."')";
                $iso_lists = kernel::database()->select($ios_sql);
                if($iso_lists){
                    foreach($iso_lists as $k => $val){
                        $original_bn_nums = md5($val['original_bn'].'_'.$val['bn'].'_'.$val['normal_num']);
                        $originalBnNums[$original_bn_nums] = $val;
                    }
                }
            }

            foreach ($lists as &$v) {
                $v['branch_bn']   = $branchInfos[$v['branch_id']]['branch_bn'];
                $v['branch_name'] = $branchInfos[$v['branch_id']]['name'];
        
                $_product = $basicMaterialObj->dump(array('material_bn' => $v['bn']), 'bm_id, material_name');
        
                #查询关联的条形码
                $_product['barcode'] = $basicMaterialBarcode->getBarcodeById($_product['bm_id']);
        
                $v['barcode']   = $_product['barcode'];
                $v['name']      = $_product['material_name'];
                $v['type_name'] = $iostocktypeInfos[$v['type_id']];
        
                if (in_array($v["type_id"], $arr_appropriation_type_ids)) {
                    $v['original_name'] = (string)$rl_original_id_appropriation_no[$v["original_id"]]['name'];
            
                    if ($v['type_id'] == '4' || $v['type_id'] == '40') {
                        $v["appropriation_no"] = $rl_original_id_appropriation_no[$v["original_id"]]['appropriation_no'];
                    }
                }
                $ios_key = md5($v['original_bn'].'_'.$v['bn'].'_'.$v['nums']);
                $v['arrival_no'] = isset($originalBnNums[$ios_key]['arrival_no']) ? $originalBnNums[$ios_key]['arrival_no'] : '';
            }

            return array(
                'lists' => $lists,
                'count' => $countList['_count'],
            );
        }else{
            return array(
                'lists' => array(),
                'count' => 0,
            );
        }
    }
}