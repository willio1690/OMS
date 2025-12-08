<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_finder_extend_filter_basic_material_storage_life{
    function get_extend_colums(){
        
        $brObj = app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        //过滤o2o门店虚拟仓库
        if ($is_super){
            $branch_rows = $brObj->getList('branch_id,name',array('b_type'=>1),0,-1);
        }else{
            $branch_rows = $brObj->getBranchByUser();
        }
        $branch_list = array();
        foreach($branch_rows as $branch){
            $branch_list [$branch['branch_id']] = $branch['name'];
        }
        
        $db['basic_material_storage_life']=array (
            'columns' => array (
                'branch_id' =>
                array (
                    'type' => $branch_list,
                    'label' => '仓库',
                    'required' => true,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'order'=>85,
                    'filtertype'=>'normal',
                    'filterdefault'=>true,
                ),
            )
        );
        return $db;
        
    }
}
