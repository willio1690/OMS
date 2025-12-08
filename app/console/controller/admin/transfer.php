<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_ctl_admin_transfer extends desktop_controller{
    var $name = "门店调拔";
    var $workground = "console_purchasecenter";

    function index($bill_type){

        $base_filter = array(
            'bill_type' =>  $bill_type,
        );
        $title = $bill_type == 'returnnormal' ? '门店退仓':'门店调拔';

        $is_super = kernel::single('desktop_user')->is_super();

        if (!$is_super){
            // 普通管理员：默认无权限
            $base_filter['appropriation_id'] = array('false');
            
            // 获取有权限的门店仓库（b_type=2 表示门店）
            $mdlOmeBranch = app::get('ome')->model('branch');
            $branchList = $mdlOmeBranch->getList('branch_id', array(
                'b_type' => '2',
                'is_ctrl_store' => '1'
            ), 0, -1);
            
            if (!empty($branchList)) {
                $branch_ids = array_column($branchList, 'branch_id');
                $where_branch_id = '('.implode(',', $branch_ids).')';
                // 调出仓或调入仓有一方有权限就可以展示
                $base_filter['filter_sql'] = "(from_branch_id in ".$where_branch_id." or to_branch_id in ".$where_branch_id.")";
                unset($base_filter['appropriation_id']); // 有权限时移除默认的false限制
            }
        }
        $params = array('title'=>$title,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'base_filter' => $base_filter,
            'orderBy' => 'appropriation_id desc'
        );

        $this->finder('taoguanallocate_mdl_appropriation', $params);
    }


    /**
     * 检查
     * @param mixed $appropriation_id ID
     * @return mixed 返回验证结果
     */
    public function check($appropriation_id){
        $approMdl   = app::get('taoguanallocate')->model('appropriation');
        
    
        if(!$appropriation_id){
            $this->splash('error', null, '审核失败');
        }
        $res = $approMdl->oneClickCheck($appropriation_id, $msg);
        if($res){
            

            $this->splash('success', $this->url.'', '审核成功');
        }else{
            $this->splash('error', null, $msg ? $msg :'审核失败');
        }
        
    }

    /**
     * finish
     * @param mixed $appropriation_id ID
     * @return mixed 返回值
     */
    public function finish($appropriation_id){
        $approLib = kernel::single('erpapi_store_response_process_appropriation');
        if(!$appropriation_id){
            $this->splash('error', null, '审批失败');
        }
        $filter = array('appropriation_id'=>$appropriation_id);
        $res = $approLib->finish($filter);
        if($res){
          
           
            $this->splash('success', $this->url.'', '审批成功');
        }else{
            $this->splash('error', null, '审批失败');
        }
        
    }
}
?>