<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_ctl_admin_inventory extends desktop_controller {

    var $name = "门店盘点";
    var $workground = "o2o_center";
    
    //分类导航
    function _views(){
        $sub_menu = array(
                0 => array('label'=>app::get('base')->_('全部'),'optional'=>false),
                1 => array('label'=>app::get('base')->_('未确认'),'filter'=>array('status'=>1),'optional'=>false),
                2 => array('label'=>app::get('base')->_('已确认'),'filter'=>array('status'=>2),'optional'=>false),
                3 => array('label'=>app::get('base')->_('作废'),'filter'=>array('status'=>3),'optional'=>false),
        );
        $mdlO2oInventory = app::get('o2o')->model("inventory");
        $i=0;
        foreach($sub_menu as $k=>$v){
            if (!IS_NULL($v['filter'])){
                $v['filter'] = $v['filter'];
            }
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $mdlO2oInventory->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=o2o&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view='.$i++;
        }
        return $sub_menu;
    }

    function index(){
        $this->title = '盘点列表';
        $this->action = array(
            
        );
        
        $base_filter = array();
        $is_super = kernel::single('desktop_user')->is_super();
        $branchObj     = kernel::single('o2o_store_branch');
        if (!$is_super){
            
            $branch_ids    = $branchObj->getO2OBranchByUser();
            
        }
        //过滤选择门店的下拉框 类别头部筛选
        $post_selected_store_bn = trim($_POST['selected_store_bn']);
        if($post_selected_store_bn && $post_selected_store_bn!="_NULL_"){
            //获取branch_id
            $mdlOmeBranch = app::get('ome')->model('branch');
            $rs_branch_id = $mdlOmeBranch->dump(array("branch_bn"=>$post_selected_store_bn,'check_permission'=>'false'),"branch_id");
            $select_branch_id = $rs_branch_id['branch_id'];
            $branch_ids = array_intersect(array($select_branch_id), $branch_ids);
        }
        $base_filter['branch_id'] = $branch_ids;
        $params = array(
                'title'=>$this->title,
                'use_buildin_recycle'=>false,
                'use_buildin_filter'=>true,
                'base_filter'=>$base_filter,
                'orderBy'=>'inventory_id DESC',
                'actions'=>$this->action,
        );
        
        //在列表上方添加过滤门店
        if(!isset($_GET['action'])) {
            $panel = new desktop_panel($this);
            $panel->setId('inventory_finder_top');
            $panel->setTmpl('admin/finder/finder_panel_filter.html');
            $panel->show('o2o_mdl_inventory', $params);
        }
    
        $this->finder('o2o_mdl_inventory', $params);
    }
    
    //导出加载页
    function export(){
        $this->page("admin/inventory/export.html");
    }
    
    //导入加载页
    function import(){
        $this->page('admin/inventory/import.html');
    }
    
    //批量的和单个的作废
    function doCancel(){
        $this->begin();
        $inventory_id = intval($_GET['inventory_id']);
        if(empty($inventory_id)){
            $this->end(false, '没有选中的盘点记录');
        }
    
        list($rs, $rsData) = kernel::single('pos_event_trigger_inventory')->cancel($inventory_id);

        if(!$rs){
            $this->end(false, '盘点单请求pos取消失败');
        }
        $mdlO2oInventory = app::get('o2o')->model('inventory');
        $rs_inventory = $mdlO2oInventory->dump(array("status|in"=>array(2,3),"inventory_id"=>$inventory_id),"inventory_id");
        if(!empty($rs_inventory)){
            $this->end(false, '请确认盘点单必须是未确认的状态');
        }
        $inventory_ids = array($inventory_id);
        $result = kernel::single('o2o_inventorylist')->cancel_inventory($inventory_ids);
        if($result){
            $this->end(true, '操作成功','javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();');
        }
    }
    
    //批量删除
    function batch_delete(){
        $this->begin();
        $inventory_ids = $_POST['inventory_id'];
        if(empty($inventory_ids)){
            $this->end(false, '请选择记录');
        }
        $mdlO2oInventory = app::get('o2o')->model('inventory');
        $rs_inventory = $mdlO2oInventory->dump(array("status"=>2,'inventory_id|in'=>$inventory_ids),'inventory_id');
        if(!empty($rs_inventory)){
            $this->end(false, '不能删除已确认的盘点单');
        }
        $result = kernel::single('o2o_inventorylist')->delete_inventory($inventory_ids);
        if($result){
            $this->end(true, '操作成功','javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();');
        }
    }
    
    //查看明细
    function detail_inventory($inventory_id=null){
       
        $inventory_id = intval($inventory_id);
        $this->pagedata["inventory_id"] = $inventory_id;
        $this->pagedata["inv_info"] = kernel::single('o2o_inventorylist')->get_basic_info($inventory_id);
        $this->pagedata["branch_id"] = $this->pagedata["inv_info"]["branch_id"];
        $itemsMdl = app::get('o2o')->model('inventory_items');

        $inventorys = $itemsMdl->db->selectrow("SELECT sum(accounts_num) as total_accounts_num,sum(pos_accounts_num) as total_pos_accounts_num,sum(actual_num) as total_actual_num,sum(amount) as total_amount FROM sdb_o2o_inventory_items WHERE inventory_id=".$inventory_id."");

        $short_items = $itemsMdl->db->selectrow("SELECT sum(short_over) as total_short FROM sdb_o2o_inventory_items WHERE inventory_id=".$inventory_id." and short_over<0");
        $over_items = $itemsMdl->db->selectrow("SELECT sum(short_over) as total_over FROM sdb_o2o_inventory_items WHERE inventory_id=".$inventory_id." and short_over>0");
        $inventorys['total_short'] = $short_items['total_short'];
        $inventorys['total_over'] = $over_items['total_over'];

        $this->pagedata['inventorys'] = $inventorys;
       
       
        $this->singlepage("admin/inventory/detail_inventory.html");

    }
    
    
    //编辑页
    function edit_inventory(){
       
    }
    
    /**
     * 获取Items
     * @return mixed 返回结果
     */
    public function getItems()
    {
        @ini_set('memory_limit','64M');
        
        $inventory_id = intval($_POST['inventory_id']);
        $diff_nums = $_POST['diff_nums'];

        $itemObj = app::get('o2o')->model('inventory_items');
        $filter = array(
            'inventory_id'  =>  $inventory_id,
        );
        
        if($diff_nums =='1'){
            $filter['filter_sql'] = "(short_over!=0)";
        }

        $item_list = $itemObj->getlist('bm_id,accounts_num,actual_num,short_over,item_id,price,amount,pos_accounts_num',$filter);
        $bm_ids = array_column($item_list, 'bm_id');
        $material_list = $this->material_list($bm_ids);
        $rows = array();

        foreach($item_list as $v){
            $materials = $material_list[$v['bm_id']];
            $v['material_bn'] = $materials ? $materials['material_bn'] : '-';
            $v['material_name'] = $materials ? $materials['material_name'] : '-';
            $rows[] = $v;
        }
        if($rows) {
            echo json_encode($rows);
            exit;
        }
        
    }

    /**
     * material_list
     * @param mixed $bm_ids ID
     * @return mixed 返回值
     */
    public function material_list($bm_ids){
        $bmMdl    = app::get('material')->model('basic_material');
        $bm_list = $bmMdl->getList('bm_id,material_bn,material_name', array('bm_id' => $bm_ids));
        $bm_list = array_column($bm_list, null, 'bm_id');
        return $bm_list;
    }

    
    //编辑页 保存
    function doEdit(){
      
    }
    
    //确认页
    function confirm_inventory($inventory_id=null, $page=1){
        $inventory_id = intval($inventory_id);
        
        $this->pagedata["inventory_id"] = $inventory_id;
        $this->pagedata["inv_info"] = kernel::single('o2o_inventorylist')->get_basic_info($inventory_id);
        $this->pagedata["branch_id"] = $this->pagedata["inv_info"]["branch_id"];

        $itemsMdl = app::get('o2o')->model('inventory_items');

        $inventorys = $itemsMdl->db->selectrow("SELECT sum(accounts_num) as total_accounts_num,sum(pos_accounts_num) as total_pos_accounts_num,sum(actual_num) as total_actual_num,sum(amount) as total_amount FROM sdb_o2o_inventory_items WHERE inventory_id=".$inventory_id."");
        
        $short_items = $itemsMdl->db->selectrow("SELECT sum(short_over) as total_short FROM sdb_o2o_inventory_items WHERE inventory_id=".$inventory_id." and short_over<0");
        $over_items = $itemsMdl->db->selectrow("SELECT sum(short_over) as total_over FROM sdb_o2o_inventory_items WHERE inventory_id=".$inventory_id." and short_over>0");
        $inventorys['total_short'] = $short_items['total_short'];
        $inventorys['total_over'] = $over_items['total_over'];
       
        $this->pagedata['inventorys'] = $inventorys;
       
        $this->singlepage("admin/inventory/confirm_inventory.html");
    }
    
    //确认页 确认
    function doConfirm(){
        $this->begin();
       
        if(!$_POST["inventory_id"] || !$_POST["branch_id"]){
            $this->end(false,'无效操作');
        }
        $invObj = app::get('o2o')->model('inventory');
        $inventory_id = $_POST['inventory_id'];

        list($rs, $rsData) = kernel::single('pos_event_trigger_inventory')->check($inventory_id);
      
        if(!$rs){
            $this->end(false, '盘点单请求pos失败');
        }
         $update_inventory_arr = array(
           'status'        => '2',
           'confirm_time'  =>time(), 
        );
        $filter_inventory_arr = array('inventory_id' => $inventory_id);
        $invObj->update($update_inventory_arr, $filter_inventory_arr);

        
        $inv_detail = $invObj->dump(array('inventory_id'=>$inventory_id),'inventory_bn,branch_id,physics_id');
        $itemObj = app::get('o2o')->model('inventory_items');
     
        $item_list = $itemObj->getlist('bm_id,accounts_num,actual_num,short_over,item_id,amount,pos_accounts_num',array('inventory_id'=>$inventory_id));

        $bm_id_list = array_column($item_list, 'bm_id');

        $bm_list = app::get('material')->model('basic_material')->getList('bm_id,material_bn,material_name', array('bm_id' => $bm_id_list));
        $bm_list = array_column($bm_list, null, 'bm_id');

        $data = array(
            'task_id'       => $inventory_id,
            'task_bn'       => $inv_detail['inventory_bn'],
            'branch_id'     => $inv_detail['branch_id'],
            'operate_type'  => 'store',
            'adjust_oper'   => $opInfo['op_name'],
            'adjust_time'   => time(),
            'physics_id'    => $inv_detail['physics_id'],
          
        );

        //判断是否已生成
        
        $items = array();
        $total_amount = 0;
        foreach($item_list as $v){
            $bm     = $bm_list[$v['bm_id']];
            if ($v['short_over'] ==0) continue;
            $items[] = array(
                'id'            => $v['item_id'],
                'bm_id'         => $v['bm_id'],
                'material_bn'   => $bm['material_bn'],
                'wms_stores'    => $v['actual_num'],
                'oms_stores'    => $v['accounts_num'],
                'diff_stores'   => $v['short_over'],
                'pos_accounts_num'=>$v['pos_accounts_num'],
            );
            $total_amount+=$v['amount'];
        }
        $data['total_amount'] = $total_amount;
        $data['items'] = $items;
        if($data['items']){
           list($rs, $rsData) = kernel::single('console_difference')->insertBill($data);
            if(!$rs) {

                $this->end(false, '差异单新建失败:'.$rsData['msg']);
            }
        }
        
        //记录日志
        $opObj = app::get('ome')->model('operation_log');
        $opObj->write_log('inventory_confirm@o2o', $inventory_id, '门店盘点单确认成功');
        
        $this->end(true,'操作成功');
    }
    

    
    //branch_id判是否有存在o2o库存
    function hasO2oStore(){
        $branch_bn = $_POST["branch_bn"];
        $mdlOmeBranch = app::get('ome')->model('branch');
        $mdlO2oProductStore = app::get('ome')->model('branch_product');
        //获取branch_id
        $rs_branch = $mdlOmeBranch->dump(array("branch_bn"=>$branch_bn,"b_type"=>2),"branch_id");
        $rs_store = $mdlO2oProductStore->dump(array("branch_id"=>$rs_branch["branch_id"]));
        if(empty($rs_store)){
            echo false;
        }else{
            echo true;
        }
    }
    
}