<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeanalysts_ctl_ome_analysis extends desktop_controller{
    /**
     * income
     * @return mixed 返回值
     */
    public function income(){ //订单金额统计
        $organization_permissions = kernel::single('desktop_user')->get_organization_permission();
        if ($organization_permissions) {
            $_POST['org_id'] = $organization_permissions;
        }
        kernel::single('omeanalysts_ome_income')->set_params($_POST)->display();
    }

    /**
     * delivery
     * @return mixed 返回值
     */
    public function delivery(){ //快递费结算表
        $oBranch = app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super) {
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids[0]>0) {
                $default_type = $branch_ids[0];
            } else {
                $default_type = 'false';
            }
        }else{
            $branchs = $oBranch->getList('branch_id,name',array(),0,1);
            if ($branchs[0]['branch_id']>0) {
                $default_type = $branchs[0]['branch_id'];
            } else {
                $default_type = 'false';
            }
        }
        $_POST['type_id'] = $_POST['type_id'] ? $_POST['type_id'] : $default_type;
        $_POST['own_branches'] = $this->getOperBranches();
        
        $organization_permissions = kernel::single('desktop_user')->get_organization_permission();
        if ($organization_permissions) {
            $_POST['org_id'] = $organization_permissions;
        }
        kernel::single('omeanalysts_ome_delivery')->set_params($_POST)->display();
    }

    /**
     * cod
     * @return mixed 返回值
     */
    public function cod(){ //货到付款结算表
        $oBranch = app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super) {
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids[0]>0) {
                $default_type = $branch_ids[0];
            } else {
                $default_type = 'false';
            }
        }else{
            $branchs = $oBranch->getList('branch_id,name',array(),0,1);
            if ($branchs[0]['branch_id']>0) {
                $default_type = $branchs[0]['branch_id'];
            } else {
                $default_type = 'false';
            }
        }
        $organization_permissions = kernel::single('desktop_user')->get_organization_permission();
        if ($organization_permissions) {
            $_POST['org_id'] = $organization_permissions;
        }
        
        $_POST['type_id'] = $_POST['type_id'] ? $_POST['type_id'] : $default_type;
        $_POST['own_branches'] = $this->getOperBranches();
        kernel::single('omeanalysts_ome_cod')->set_params($_POST)->display();
    }


    /**
     * shop
     * @return mixed 返回值
     */
    public function shop(){ //店铺销售情况
        //取消当天实时数据统计，这样做会导致每请求一次增加一批垃圾数据
        /*if(empty($_POST['time_from'])){
          kernel::single('omeanalysts_analysis_shop_shop')->analysis_data();#用于统计当天实时的数据
        }*/
        if(empty($_POST) && !isset($_REQUEST['time_from']) && !isset($_REQUEST['time_to'])){
            $_POST['time_from'] = date('Y-m-d', strtotime("-1 day"));
            $_POST['time_to'] = date('Y-m-d', strtotime("-1 day"));
        }
        kernel::single('omeanalysts_ome_shop')->set_params($_POST)->display();
    }
    
    //货品销售情况
    /**
     * products
     * @return mixed 返回值
     */
    public function products()
    {
        kernel::single('omeanalysts_ctl_ome_goodsale')->mod_query_time();
        if($_POST['org_id']){
            if(!is_array($_POST['org_id'])){
                $_POST['org_id'] = array($_POST['org_id']);
            }
        }else{
            $organization_permissions = kernel::single('desktop_user')->get_organization_permission();
            if ($organization_permissions) {
                $_POST['org_id'] = $organization_permissions;
            }
        }
        //类型
        if($_POST['material_type'] == 'sales_material' || $_GET['material_type'] == 'sales_material'){
            //销售物料
            kernel::single('omeanalysts_sales_goods')->set_params($_POST)->display();
        }elseif ($_POST['material_type'] == 'sales_and_basic_material' || $_GET['material_type'] == 'sales_and_basic_material'){
            //基础物料(按照：店铺 + 销售物料 + 基础物料 的纬度统计数据)
            kernel::single('omeanalysts_sales_products')->set_params($_POST)->display();
        }else{
            //基础物料
            kernel::single('omeanalysts_ome_products')->set_params($_POST)->display();
        }
    }
    
    /**
     * goodsrank
     * @return mixed 返回值
     */
    public function goodsrank(){ //商品销售排行
        kernel::single('omeanalysts_ome_goodsrank')->set_params($_POST)->display();
    }

    /**
     * sales
     * @return mixed 返回值
     */
    public function sales(){ //订单销售情况
        kernel::single('omeanalysts_ctl_ome_goodsale')->mod_query_time();
        $_POST['own_branches'] = $this->getOperBranches();
        $organization_permissions = kernel::single('desktop_user')->get_organization_permission();
        if ($organization_permissions) {
            $_POST['org_id'] = $organization_permissions;
        }
        kernel::single('omeanalysts_ome_sales')->set_params($_POST)->display();
    }

    // 发货销售统计
    /**
     * salesDeliveryOrdeItem
     * @return mixed 返回值
     */
    public function salesDeliveryOrdeItem()
    {
        kernel::single('omeanalysts_ctl_ome_goodsale')->mod_query_time();
        $_POST['own_branches'] = $this->getOperBranches();
        $organization_permissions = kernel::single('desktop_user')->get_organization_permission();
        if ($organization_permissions) {
            $_POST['org_id'] = $organization_permissions;
        }
        kernel::single('omeanalysts_sales_delivery_order_item')->set_params($_POST)->display();
    }

    /**
     * store
     * @return mixed 返回值
     */
    public function store(){ //库存报表
        kernel::single('omeanalysts_ome_store')->set_params($_POST)->display();
    }


    /**
     * aftersale
     * @return mixed 返回值
     */
    public function aftersale(){ //货品售后问题统计
        $organization_permissions = kernel::single('desktop_user')->get_organization_permission();
        if ($organization_permissions) {
            $_POST['org_id'] = $organization_permissions;
        }
        kernel::single('omeanalysts_ome_aftersale')->set_params($_POST)->display();
    }

    /**
     * regenerate_report
     * @param mixed $params 参数
     * @param mixed $action action
     * @return mixed 返回值
     */
    public function regenerate_report($params = 'shop',$action = 'regenerate'){//重新生成报表
        kernel::single('omeanalysts_analysis_shop_shop')->$action();
    }

    /**
     * branchdelivery
     * @return mixed 返回值
     */
    public function branchdelivery(){ //仓库发货情况统计
        $_POST['own_branches'] = $this->getOperBranches();
        $organization_permissions = kernel::single('desktop_user')->get_organization_permission();
        if ($organization_permissions) {
            $_POST['org_id'] = $organization_permissions;
        }
        kernel::single('omeanalysts_ome_branchdelivery')->set_params($_POST)->display();
    }

    private function getOperBranches(){
        $oBranch = app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super) {
            $branch_ids = $oBranch->getBranchByUser(true);
            if (count($branch_ids)>0) {
                return $branch_ids;
            } else {
                return array(0);
            }
        }
    }
    
    /**
     * refundNoreturn
     * @return mixed 返回值
     */
    public function refundNoreturn()
    {
        kernel::single('omeanalysts_ome_refundNoreturn')->set_params($_POST)->display();
    }
}
