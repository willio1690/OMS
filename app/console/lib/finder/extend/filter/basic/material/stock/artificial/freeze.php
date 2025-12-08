<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 人工库存预占
 */

class console_finder_extend_filter_basic_material_stock_artificial_freeze{
    
    function get_extend_colums(){
        
        //仓库列表
        $branchObj = app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        $branch_ids = kernel::single('wms_branch')->getBranchwmsByUser($is_super, false);
        $branch_rows   = $branchObj->getList('branch_id, name',array('branch_id'=>$branch_ids),0,-1);
        $branch_list = array();
        foreach($branch_rows as $branch){
            $branch_list[$branch['branch_id']] = $branch['name'];
        }
        
        //获取预占中的组数据
        $mdl_mafg = app::get('material')->model('basic_material_stock_artificial_freeze_group');
        if(!$is_super){ //非超级管理员 显示管辖仓库下的组信息
            $mdl_maf = app::get('material')->model('basic_material_stock_artificial_freeze');
            $rs_group_ids = $mdl_maf->db->select("select group_id from sdb_material_basic_material_stock_artificial_freeze where branch_id in('".implode("','",$branch_ids)."') group by group_id");
            $group_ids = array();
            foreach($rs_group_ids as $var_gi){
                if($var_gi["group_id"]){
                    $group_ids[] = $var_gi["group_id"];
                }
            }
            $rs_group = array();
            if(!empty($group_ids)){
                $rs_group = $mdl_mafg->getList("*",array("group_id"=>$group_ids));
            }
        }else{
            $rs_group = $mdl_mafg->getList();
        }
        $group_list = array();
        if(!empty($rs_group)){
            foreach($rs_group as $key_group => $var_group){
                $group_list[$var_group["group_id"]] = $var_group["group_name"];
            }
        }
        
        //columns
        $db['basic_material_stock_artificial_freeze']=array (
                'columns' => array (
                        'branch_id' =>
                        array (
                            'type' => $branch_list,
                            'editable' => false,
                            'label' => '仓库',
                            'width' => 110,
                            'filtertype' => 'normal',
                            'filterdefault' => true,
                            'in_list' => true,
                            'panel_id' => 'stock_artificial_freeze_finder_top',
                        ),
                        'group_id' =>
                        array (
                            'type' => $group_list,
                            'editable' => false,
                            'label' => '组',
                            'width' => 110,
                            'filtertype' => 'normal',
                            'filterdefault' => true,
                            'in_list' => true,
                            'panel_id' => 'stock_artificial_freeze_finder_top',
                        ),
                        'basic_material_bn'=>  array (
                            'type' => 'varchar(200)',
                            'editable' => false,
                            'label' => '基础物料编码',
                            'filtertype' => 'normal',
                            'filterdefault' => true,
                            'panel_id' => 'stock_artificial_freeze_finder_top',
                        ),
                )
        );
        
        return $db;
    }
}