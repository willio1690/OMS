<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * o2o门店线下仓库
 *
 * @access public
 * @author wangbiao<wangbiao@shopex.cn>
 * @version $Id: regions.php 2016-03-29 20:00
 */
class o2o_store_branch
{
    /**
     * 获取操作员管辖的o2o门店线下仓库
     * 支持经销商权限继承
     * 
     * @param intval $region_id
     * @return Array
     */

    public function getO2OBranchByUser($dataType=null)
    {
        $opInfo = kernel::single('ome_func')->getDesktopUser();
        $op_id = $opInfo['op_id'];

        // 使用新的权限继承服务获取所有有权限的branch_id
        $permissionService = kernel::single('organization_organization_permission');
        $bps = $permissionService->expandUserBranchIds($op_id, 'offline'); // 只获取门店类型
        
        // 注释掉原来的逻辑，因为加入经销商逻辑后，branch_ops表可能不全
        /*
        $oBops    = app::get('ome')->model('branch_ops');
        
        $filter    = array('op_id' => $op_id);

        $bops_list = $oBops->getList('branch_id', $filter, 0, -1);
        if ($bops_list)
        {
            foreach ($bops_list as $k => $v)
            {
                $bps[] = $v['branch_id'];
            }
        }
        */
        
        if ($dataType)
        {
            return $bps;
        }
        
        if ($bps)
        {
            $Obranch  = app::get('ome')->model('branch');
            $branch_list = $Obranch->getList('branch_id,name,uname,phone,mobile', array('branch_id' => $bps), 0, -1);
        }
        
        if ($branch_list)
        {
            ksort($branch_list);
        }
        
        return $branch_list;
    }

    /**
     * 获取O2OBranchIds
     * @param mixed $dataType 数据
     * @return mixed 返回结果
     */
    public function getO2OBranchIds($dataType=null)
    {
        $oBops    = app::get('ome')->model('branch_ops');
        
        $opInfo = kernel::single('ome_func')->getDesktopUser();
        $op_id = $opInfo['op_id'];
       
        $filter    = array('op_id' => $op_id);

        $bops_list = $oBops->getList('branch_id', $filter, 0, -1);

        
        $orgs = array();
        if ($bops_list)
        {
            foreach ($bops_list as $k => $v)
            {
                if($v['branch_id']==0){
                    $orgs = $v['org'];
                }else{
                    $bps[] = $v['branch_id'];
                }
                
            }
        }
        
        if ($bps)
        {
            return $bps;
        }
        
        if($orgs){

            $orgMdl = app::get('organization')->model('organization');
            $orgs = explode(':',$orgs);
            
            $org_detail = $orgMdl->db_dump(array('org_id'=>$orgs[2]),'*');

            $org_no = $org_detail['org_no'];
        
            $store_list = $orgMdl->get_all_children($org_no);
          
            $store_bns = array_column($store_list, 'org_no');
           
            $storeMdl = app::get('o2o')->model('store');
            $stores = $storeMdl->getlist('branch_id',array('store_bn'=>$store_bns));

            $branch_ids = array_column($stores,'branch_id');
            return $branch_ids;

        }

        return [];
    }


    /**
     * 获取O2omanbranchs
     * @param mixed $branch_ids ID
     * @return mixed 返回结果
     */
    public function getO2omanbranchs($branch_ids){

        $branchMdl  = app::get('ome')->model('branch');
        $branch_list = $branchMdl->getList('branch_id', array('branch_id' => $branch_ids,'type'=>'main','check_permission'=>'false'), 0, -1);

        if($branch_list){

            $branch_ids = array_column($branch_list,'branch_id');

            return $branch_ids;
        }
    }
}
