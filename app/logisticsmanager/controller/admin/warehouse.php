<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_ctl_admin_warehouse extends desktop_controller
{

    /**
     * index
     * @return mixed 返回值
     */
    public function index() {
        $actions = array();
        $actions[] = array(
                    'label' => '新建区域仓',
                    'href' => 'index.php?app=logisticsmanager&ctl=admin_warehouse&act=add',
                    'target' => "dialog::{width:800,height:600,title:'新建区域仓'}",
        );
       
        $actions[] = array(
            'label'=>'同步前端',
            'submit'=>'index.php?app=logisticsmanager&ctl=admin_warehouse&act=syncShop',
            'target'=>"dialog::{width:600,height:400,title:'同步前端'}",
        );


        $params = array(
            'title'=>'区域仓管理',
            'actions'               => $actions,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'orderBy'=> 'id DESC',
        );
        $this->finder('logisticsmanager_mdl_warehouse', $params);


    }

    /**
     * 添加
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function add($id=0){

        $branchObj = app::get('ome')->model('branch');
        $branchList = $branchObj->getlist('branch_id,name',array('b_type'=>1,'type'=>'main','is_deliv_branch'=>'true'));

        $this->pagedata['branchList'] = $branchList;

        $warehouseObj = app::get('logisticsmanager')->model('warehouse');
        $warehouse = $warehouseObj->dump(array('id'=>$id),'*');
        $this->pagedata['warehouse'] = $warehouse;

        $this->display("admin/warehouse/add.html");

    }



    /**
     * doSave
     * @return mixed 返回值
     */
    public function doSave(){

        $this->begin();

        if (empty($_POST['branch_id'])){
            $this->end(false,'请选择仓库');
        }
        if (empty($_POST['p_region_id'])){
            $this->end(false,'请选择覆盖区域');
        }
        $pRegionId = json_decode($_POST['p_region_id']);
        $operator = kernel::single('desktop_user')->get_name();
        $warehouseObj = app::get('logisticsmanager')->model('warehouse');
        $branchObj = app::get('ome')->model('branch');
        
        // 获取b_type，默认为1（仓库）
        $b_type = isset($_POST['b_type']) ? $_POST['b_type'] : 1;
        
        $branch = $branchObj->dump(array('branch_id'=>$_POST['branch_id'],'check_permission'=>'false'),'branch_bn');
        if (!$branch) {
            $this->end(false, '仓库不存在');
        }
        
        $id = $_POST['id'];

        $addressIds = explode(',',implode(',', $pRegionId));
        $oneLevelLocalAddressName = app::get('eccommon')->model('regions')->getList('local_name,region_grade',array('region_id'=>$addressIds));
        $oneAddressName = '';
        $addressName = '';
        if ($oneLevelLocalAddressName) {
            foreach ($oneLevelLocalAddressName as $value) {
                if ($value['region_grade'] == 1) {
                    $oneAddressName .= $value['local_name'] . ',';
                }
            }
            if (substr($oneAddressName,-1,1) == ',') {
                $oneAddressName = substr($oneAddressName, 0, (strlen($oneAddressName)-1));
            }
            $addressName = implode(',', array_column($oneLevelLocalAddressName,'local_name'));
        }
        
        if($id){
            $update_data = array(
                'branch_id'              => $_POST['branch_id'],
                'warehouse_name'         => trim($_POST['warehouse_name']),
                'region_ids'             => implode(',', $pRegionId),
                'region_names'           => $addressName,
                'branch_bn'              => $branch['branch_bn'],
                'one_level_region_names' => $oneAddressName,
                'b_type'                 => $b_type,
            );
            $warehouseObj->update($update_data,array('id'=>$id));

        }else{
            $warehouse = $warehouseObj->dump(array('branch_id'=>$_POST['branch_id'], 'b_type'=>$b_type),'id');
            if ($warehouse){
                $this->end(false,'系统仓库ID重复，请重新填写');
            }

            $warehouse = $warehouseObj->dump(array('region_ids'=>implode(',', $pRegionId)),'id');

            if ($warehouse){
                $this->end(false,'区域仓覆盖范围不可重合，请重新填写');
            }

            // 只有仓库类型才需要库存查询地址
            if ($b_type == 1 && empty($_POST['area'])){
                $this->end(false,'库存查询地址不可为空');
            }
            
            $warehouse = $warehouseObj->dump(array('warehouse_name'=>trim($_POST['warehouse_name']), 'b_type'=>$b_type),'id');

            if ($warehouse){
                $this->end(false,'区域仓名称:'.trim($_POST['warehouse_name']).'已存在');
            }
            $insert_data = array(
                'branch_id'              => $_POST['branch_id'],
                'branch_bn'              => $branch['branch_bn'],
                'warehouse_name'         => trim($_POST['warehouse_name']),
                'region_ids'             => implode(',', $pRegionId),
                'region_names'           => $addressName,
                'create_time'            => time(),
                'warn_num'               => isset($_POST['warn_num']) ? $_POST['warn_num'] : 5,
                'operator'               => $operator,
                'one_level_region_names' => $oneAddressName,
                'b_type'                 => $b_type,
            );
        
            $rs = $warehouseObj->insert($insert_data);

            if($rs && $b_type == 1){
                // 只有仓库类型才保存地址信息
                $area_data = array();
                foreach($_POST['area'] as $k=>$v){
                    $area = explode(':',$v);
                    list($province,$city,$street) = explode('/',$area[1]);
                    $area_data[] = array(
                        'warehouse_id'  =>  $insert_data['id'], 
                        'area'          =>  $v,
                        'province'      =>  $province,
                        'city'          =>  $city,
                        'street'        =>  $street,
                        'address'       =>  $_POST['addr'][$k],

                    );
                }

                $addressObj = app::get('logisticsmanager')->model('warehouse_address');

                $sql = ome_func::get_insert_sql($addressObj, $area_data);
                kernel::database()->exec($sql);


            }
        }

        
        $this->end(true,'区域仓设置成功');
    }


    /**
     * edit
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function edit($id){

        
        $warehouseObj = app::get('logisticsmanager')->model('warehouse');
        $warehouse = $warehouseObj->dump(array('id'=>$id),'*');
        $regionIds = $warehouse['region_ids'];
        if (!empty($warehouse['one_level_region_names'])) {
            $regionIds = explode(',',$regionIds);
            $ids = array();
            foreach ($regionIds as $key) {
                if (strrpos($key,',')) {
                    $ids[] = substr($key,strrpos($key,',')+1);
                }else{
                    $ids[] = $key;
                }
            }
            $ids = implode(',',$ids);
        }else{
            $ids = $regionIds;
        }
        $warehouse['region_ids'] = addslashes(json_encode(explode(',',$ids)));
        $this->pagedata['warehouse'] = $warehouse;

        $this->display("admin/warehouse/edit.html");

    }

    /**
     * doEdit
     * @return mixed 返回值
     */
    public function doEdit(){

        $this->begin();

        
        $warehouseObj = app::get('logisticsmanager')->model('warehouse');
        
        $id = $_POST['id'];
        if($id){
            $update_data = array(
              
                'warehouse_name' =>  trim($_POST['warehouse_name']),
                'warn_num'       =>  $_POST['warn_num'],
            );
            $warehouseObj->update($update_data,array('id'=>$id));

        }

        
        $this->end(true,'区域仓修改成功');
    }
    /**
     * showRegionList
     * @param mixed $serid ID
     * @param mixed $multi multi
     * @return mixed 返回值
     */
    public function showRegionList($serid=0,$multi=false)
    {
        
        $region_id = $_GET['region_id'];
        $region_ids = $region_id ? explode(',',$region_id) : '';

        $regionlist = kernel::single('eccommon_platform_regions')->getRegionList();
        $this->pagedata['region_ids'] = $region_ids;
        $this->pagedata['regionlist'] = $regionlist;
        $this->pagedata['multi'] =  'true';
        $this->page('admin/warehouse/regionSelect1.html');
    }

    /**
     * syncShop
     * @return mixed 返回值
     */
    public function syncShop(){

        $shopList    = app::get('ome')->model('shop')->getList('shop_id,name,node_type', array('filter_sql' => " ( node_id is not null or node_id!='' ) and node_type='luban'"));

        $this->pagedata['shopList'] = $shopList;

        $regionList = kernel::single('eccommon_platform_regions')->getRegionList('luban');

        $this->pagedata['regionList'] = $regionList;

        $warehouseObj = app::get('logisticsmanager')->model('warehouse');

        $warehouse = $warehouseObj->getlist('warehouse_name',array('id'=>$_POST['id']));
        $this->pagedata['ids'] = json_encode($_POST['id']);


        $this->display('admin/warehouse/syncshop.html');

    }

    /**
     * doSyncShop
     * @return mixed 返回值
     */
    public function doSyncShop(){

        $this->begin();
        $shop_ids = $_POST['shop_ids'];

        if (empty($shop_ids)) {
            $this->end(false,'请选择店铺');
        }
        
        $ids = $_POST['ids'];

        if (empty($ids)){
            $this->end(false,'请选择区域仓');
        }

        $warehouseLib = kernel::single('logisticsmanager_warehouse');
        $warehouseObj = app::get('logisticsmanager')->model('warehouse');
        $ids = json_decode($ids,true);
        foreach($ids as $id){
            // 检查是否为门店类型
            $warehouse = $warehouseObj->dump(array('id'=>$id), 'b_type');
            if ($warehouse && $warehouse['b_type'] == 2) {
                $this->end(false,'门店类型不支持同步');
            }
            
            //区域不存在
            $msg = '';
            if(!$warehouseLib->checkRegion($id,$msg)){
                $this->end(false,$msg.'覆盖区域异常');
            }
            $warehouseLib->sync($id,$shop_ids);
        }
        
        $this->end(true,'获取成功');


    }

    function addNewAddress(){
        if (isset($_GET['area'])){
            $this->pagedata['region'] = $_GET['area'];
        }
        $this->display("admin/warehouse/add_new_address.html");
    }

    /**
     * 检查Area
     * @return mixed 返回验证结果
     */
    public function checkArea(){
        $areas = $_POST['areas'];
        list($province,$city,$street) = explode('/',$areas);
        $regionsObj = app::get('eccommon')->model('platform_regions');
        $regions = $regionsObj->dump(array('shop_type'=>'360buy','region_grade'=>3,'outregion_name'=>$street),'id');

        if($regions){
            $rs = array('rsp'=>'succ');
        }else{
            $rs = array('rsp'=>'succ');
        }
        echo json_encode($rs);
    }
}




?>