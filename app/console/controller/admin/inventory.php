<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_ctl_admin_inventory extends desktop_controller{
    var $workground = "console_center";
    function index(){
        $base_filter = array();
        $is_super = kernel::single('desktop_user')->is_super();
        
        if (!$is_super){
            // 普通管理员：默认无权限
            $base_filter['branch_bn'] = array('false');
            
            // 获取有权限的门店仓库（b_type=2 表示门店）
            $mdlOmeBranch = app::get('ome')->model('branch');
            $branchList = $mdlOmeBranch->getList('branch_bn', array(
                'b_type' => '2',
                'is_ctrl_store' => '1'
            ), 0, -1);
            
            if (!empty($branchList)) {
                $base_filter['branch_bn'] = array_column($branchList, 'branch_bn');
            }
        }
        // 超级管理员：base_filter 为空数组，不做权限限制
        
        $params = array(
            'title'=>'盘点单查看',
            'use_buildin_recycle'=>false,
            'orderBy' => 'inventory_date desc',
            'base_filter' => $base_filter,
        );
        
        $this->finder('console_mdl_inventory',$params);
    }
    
    function view_item(){
        $base_filter = array();
        if ($_GET['inventory_id']){
            $base_filter = array('inventory_id'=>$_GET['inventory_id']);
        }
        $params = array(
            'title'=>'盘点单详情',
            'use_buildin_recycle'=>false,
            'use_buildin_selectrow'=>false,
            'base_filter'=>$base_filter,
        );
        $this->finder('console_mdl_inventory_items',$params);
    }
}