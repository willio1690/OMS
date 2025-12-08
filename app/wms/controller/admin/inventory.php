<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_ctl_admin_inventory extends desktop_controller{

	var $name = "盘点表管理";
    var $workground = "wms_center";

    function _views(){
        $sub_menu = $this->_views_pd();
        return $sub_menu;
    }
    function _views_pd(){
        $mdl_inventory = app::get('taoguaninventory')->model("inventory");
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'),'optional'=>false),
            1 => array('label'=>app::get('base')->_('待确认'),'filter'=>array('confirm_status' =>array(1,4)),'optional'=>false),
            2 => array(
                'label'=>app::get('base')->_('已确认'),
                'filter'=>array('confirm_status' =>'2'),
                'optional'=>false),
            3 => array(
                'label'=>app::get('base')->_('已作废'),
                'filter'=>array('confirm_status' =>'3'),
                'optional'=>false),
            );


        $i=0;
        foreach($sub_menu as $k=>$v){
            if (!IS_NULL($v['filter'])){
                $v['filter'] = $v['filter'];
            }
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $mdl_inventory->count($v['filter']);
           
            $sub_menu[$k]['href'] = 'index.php?app=wms&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&flt='.$_GET['flt'].'&view='.$i++;
        }
        return $sub_menu;
    }


    function index(){
        switch ($_GET['flt']) {
            case 'list':
                $this->title = '盘点列表';
                    $this->action = array(
                        array('label' =>'新建', 'href' => 'index.php?app=wms&ctl=admin_inventory&act=inventory_selectbranch'),
                        array('label' =>'模板导出', 'href' => 'index.php?app=wms&ctl=admin_inventory&act=export&flt='.$_GET['flt'], 'target' => 'dialog::{width:700,height:400,title:\'模板导出\'}'),
                         array('label' =>'盘点导入', 'href' => 'index.php?app=wms&ctl=admin_inventory&act=import', 'target' => 'dialog::{width:700,height:400,title:\'盘点导入\'}'),
                         array('label'=>app::get('desktop')->_('作废'),'icon'=>'add.gif','confirm'=>app::get('desktop')->_('确定作废选中项？作废后将不可恢复'),'submit'=>'index.php?app=wms&ctl=admin_inventory&act=batch_cancel'),
                        array('label'=>app::get('desktop')->_('删除'),'icon'=>'add.gif','confirm'=>app::get('desktop')->_('确定删除选中项？删除后将不可恢复'),'submit'=>'index.php?app=wms&ctl=admin_inventory&act=batch_delete'),
                        );
                break;
            case 'confirm':
                $this->title = '盘点确认';
                $this->action=array();
                break;
        }
        $params = array(
                        'title'=>$this->title,
                        'use_buildin_new_dialog' => false,
                        'use_buildin_set_tag'=>false,
                        'use_buildin_recycle'=>false,
                        'use_buildin_export'=>false,
                       
                        'use_buildin_filter'=>true,
                        'orderBy'=>'inventory_id DESC',
                        'actions'=>$this->action,
                    );
          /*
         * 获取操作员管辖仓库
         */
        $oBranch = app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids){
                $params['base_filter']['branch_id'] = $branch_ids;
            }else{
                $params['base_filter']['branch_id'] = 'false';
            }
        }
        #盘点，模板导出时，过滤仓库
        if(isset($_POST['branch_id'])){
            $params['base_filter']['branch_id'] = $_POST['branch_id'];
        }
           # 在列表上方添加搜索
        if(!isset($_GET['action'])) {
            $panel = new desktop_panel($this);
          
            $panel->setId('wmsinventory_finder_top');
            $panel->setTmpl('admin/finder/finder_panel_filter.html');
            
            $panel->show('taoguaninventory_mdl_inventory', $params);

        }
        
        $this->finder('wms_mdl_inventory', $params);
    }

    /*
     * 盘点明细
     */
    function detail_inventory($inventory_id=null, $page=1){
        set_time_limit(0);
        $inventory_id = intval($inventory_id);
        $is_auto = $_GET['is_auto'];

        $page = intval($page);
        $page = $page ? $page : 1;
        $pagelimit = 10;
        $oInventory = app::get('taoguaninventory')->model('inventory');
        $oInventory_items = app::get('taoguaninventory')->model('inventory_items');
        $inventory_detail = $oInventory->dump($inventory_id, '*');

        $shortage_over = $_GET['shortage_over'];
        if(($is_auto=='0') || ($is_auto=='1')){

            $filter = array('inventory_id'=>$inventory_id,'is_auto'=>$is_auto);
            $total = $oInventory->getInventoryTotal($inventory_id,$is_auto,$shortage_over);
        }else{
            $filter = array('inventory_id'=>$inventory_id);
            $total = $oInventory->getInventoryTotal($inventory_id,'',$shortage_over);
        }
        if($_GET['shortage_over']==1){
            $show_shortage_over = $_GET['shortage_over'];
            $filter['shortage_over|noequal']=0;

        }

        //盘点明细

        $inventory_items = $oInventory_items->getList('*', $filter, $pagelimit*($page-1), $pagelimit,'is_auto ASC,bn DESC');
        $branch_id =  $inventory_detail['branch_id'];

        $basicMStorageLifeLib    = kernel::single('material_storagelife');
        
        $total_price = 0;
        #盈亏总金额
        $total_shortage_over_price = 0;
        if ($inventory_items)
        foreach ($inventory_items as $k=>$v){
            #成本价
            $price = kernel::single('taoguaninventory_inventorylist')->get_price($v['product_id'],$branch_id);
            if (!kernel::single('desktop_user')->has_permission('cost_price')) {
                $price = '-';
            }
            $inventory_items[$k]['freeze_num'] = kernel::single('material_basic_material_stock_freeze')->getBranchFreeze($v['product_id'], $branch_id);
            $inventory_items[$k]['price'] = $price;
            #盈亏数量 =实际数量-账面数量
            $shortage_over = $v['actual_num']-$v['accounts_num'];
            #盈亏金额
            $shortage_over_price = $price * $shortage_over;
            $inventory_items[$k]['shortage_over_price'] = $shortage_over_price;
            $total_price += $price;
            $total_shortage_over_price  += $shortage_over_price;
            
             //小计
             $subtotal['accounts_num'] += $v['accounts_num'];
             $subtotal['actual_num'] += $v['actual_num'];
             $subtotal['shortage_over'] += $v['shortage_over'];
             
             #是否有保质期物料
             $inventory_items[$k]['is_use_expire']    = $basicMStorageLifeLib->checkStorageLifeById($v['product_id']);
        }
        $total[0]['price'] = $total_price;
        $total[0]['shortage_over_price'] = $total_shortage_over_price;
        $count = $total['count'];

        $pager = $this->ui()->pager(array(
            'current'=>$page,
            'total'=>ceil($count/$pagelimit),
            'link'=>'index.php?app=wms&ctl=admin_inventory&act=detail_inventory&p[0]='.$inventory_id.'&p[1]=%d&view='.$_GET['view'].'&from='.$_GET['from'].'&is_auto='.$is_auto.'&shortage_over='.$shortage_over,
        ));
        
        $this->pagedata['pager'] = $pager;
        $this->pagedata['detail'] = $inventory_detail;
        $this->pagedata['items'] = $inventory_items;
        $this->pagedata['inventory_id'] = $inventory_id;
        $this->pagedata['subtotal'] = $subtotal;#小计
        $this->pagedata['total'] = $total[0];#总计
        $this->pagedata['is_auto'] = $is_auto;
        $this->pagedata['count'] = $count;
        $this->pagedata['pagelimit'] = $pagelimit;
        $this->pagedata['view'] = $_GET['view'];
        $this->page("admin/inventory/detail_inventory.html");
    }


    function add_inventory(){
        $branch_id = $_POST['branch_id'];

        if(!$branch_id){
            $this->begin("index.php?app=wms&ctl=admin_inventory&act=inventory_selectbranch");
            $this->end(false,'请选择仓库');
        }
        $brObj = app::get('ome')->model('branch');

        $branch_product = kernel::single('taoguaninventory_inventorylist')->check_product_iostock($branch_id);

        $mdl_Inventory = app::get('taoguaninventory')->model('inventory');
        $is_super = kernel::single('desktop_user')->is_super();
        $branch = $brObj->dump(array('branch_id'=>$branch_id),'name,branch_id');
        $inventory_list = $mdl_Inventory->getlist('inventory_name,inventory_date,op_name,inventory_id',array('confirm_status'=>'1','branch_id'=>$branch_id),0,-1);

        $this->pagedata['branch_product'] = $branch_product;
        $this->pagedata['date']         = date("Y-m-d");
        $this->pagedata['inventory_name'] = date('Ymd').$branch['name'];
        $this->pagedata['op_name'] =  kernel::single('desktop_user')->get_name();
        $this->pagedata['branch'] = $branch;
        $this->pagedata['inventory_list'] = $inventory_list;
        $this->page("admin/inventory/addonline.html");
    }

    function addtoInventory(){
        $this->begin();
        $branch_id = $_POST['branch_id'];
        $brObj = app::get('ome')->model('branch');
        $branch = $brObj->dump($branch_id,'name,branch_id');
        $inventory_name = $_POST['inventory_name'];
        /*新建盘点计划主表*/
        $invObj = app::get('taoguaninventory')->model('inventory');
        $inventory = $invObj->dump(array('inventory_name'=>$inventory_name),'inventory_id');
        $inventory_data = $_POST;
        $inventory_data['branch_name']=$branch['name'];
        if($inventory_data['inventory_type']==2){
            $inv_exist = $invObj->dump(array('branch_id'=>$branch_id,'inventory_type'=>2,'confirm_status'=>1),'inventory_id');

            if($inv_exist){
                $this->end(false,'此仓库已有全盘的盘点单存在');
            }
            $inv_exist1 = $invObj->dump(array('branch_id'=>$branch_id,'inventory_type'=>3,'confirm_status'=>1),'inventory_id');
             if($inv_exist1){
                $this->end(false,'请将部分盘点确认后再新建全盘');
            }
        }
        if($inventory_data['inventory_type']==1 || $inventory_data['inventory_type']==3 ){
             $inv_exist2 = $invObj->dump(array('branch_id'=>$branch_id,'inventory_type'=>2,'confirm_status'=>1),'inventory_id');
             if($inv_exist2){
                $this->end(false,'请将此仓库全盘确认后再新建部分盘点');
            }
        }
        if($inventory_data['inventory_type']==4){
            $branch_product = kernel::single('taoguaninventory_inventorylist')->check_product_iostock($branch_id);
            if($branch_product){

                   $this->end(false,'此仓库已存在进出库商品不可以期初盘点');
                    return false;
                }
                $branch_inventory = kernel::single('taoguaninventory_inventorylist')->get_inventorybybranch_id($branch_id);
                if($branch_inventory){
                    $this->end(false,'此仓库已有类型为期初的盘点单存在!');

                    return false;
                }

        }
        if($inventory){
            $this->end(false,'此盘点名称已存在,您可以选择加入,或者修改盘点名称');
        }
       
        $inventory_id = kernel::single('taoguaninventory_inventorylist')->create_inventory($inventory_data,$msg);

        if($inventory_id){
            $this->end(true, '跳转中。.', 'index.php?app=wms&ctl=admin_inventory&act=go_inventory&inventory_id='.$inventory_id);
         }else{
            $this->end(false, $msg, 'index.php?app=wms&ctl=admin_inventory&act=add_invertory');
         }
    }
    /**
    *盘点加入
    */
    function go_inventory(){
        set_time_limit(0);
        $invObj = app::get('taoguaninventory')->model('inventory');
        $oInventory_items = app::get('taoguaninventory')->model('inventory_items');
        $inventory_id = $_GET['inventory_id'];
        $page = intval($_GET['page']);
        $page = $page ? $page : 1;
        $pagelimit = 3;
        $op_name = kernel::single('desktop_user')->get_name();
        $inventory = $invObj->dump($inventory_id,'inventory_name,branch_id,branch_name,pos,inventory_id,memo,op_name,add_time,inventory_type');
        $refresh = kernel::single('taoguaninventory_inventorylist')->refresh_shortage_over($inventory_id,$inventory['branch_id']);

        $this->pagedata['inventory'] = $inventory;

        $this->pagedata['pos'] = $inventory['pos'];

        $this->pagedata['date']         = date("Y年m月d日");
        //盘点明细
        $inventory_items = $oInventory_items->getList('*', array('inventory_id'=>$inventory_id,'is_auto'=>'0'), $pagelimit*($page-1), $pagelimit,'oper_time desc');
        //总计
        $total = $invObj->getInventoryTotal($inventory_id,0);

        $basicMStorageLifeLib    = kernel::single('material_storagelife');
        
        if ($inventory_items)
        foreach ($inventory_items as $k=>$v){
             //小计
             $subtotal['accounts_num'] += $v['accounts_num'];
             $subtotal['actual_num'] += $v['actual_num'];
             $subtotal['shortage_over'] += $v['shortage_over'];
             
             #是否有保质期物料
             $inventory_items[$k]['is_use_expire']    = $basicMStorageLifeLib->checkStorageLifeById($v['product_id']);
        }
        $count = $total['count'];
         $pager = $this->ui()->pager(array(
            'current'=>$page,
            'total'=>ceil($count/$pagelimit),
            'link'=>'index.php?app=wms&ctl=admin_inventory&act=go_inventory&inventory_id='.$inventory_id.'&page=%d',
        ));
        
        $this->pagedata['subtotal'] = $subtotal;
        $this->pagedata['total'] = $total[0];
        $this->pagedata['pager'] = $pager;
        $this->pagedata['items'] = $inventory_items;
        $this->pagedata['pagelimit'] = $pagelimit;
        $this->pagedata['count'] = $count;
        $this->page("admin/inventory/addinventory_online.html");
    }

    //新建盘点
    function create_inventory(){
        $this->begin('index.php?app=wms&ctl=admin_inventory&act=go_inventory&inventory_id='.$_POST['inventory_id']);
        $inventory_id = $_POST['inventory_id'];
        if (!$_POST['branch_id']){
            $this->end(false, '无仓库信息');
        }

        if($_POST['pos']=='1'){
            if(!$_POST['pos_name']){
                $this->end(false, '无货位信息');
            }
        }

        if ($_POST['number'] == 0 || !empty($_POST['number'])){
            if (!is_numeric($_POST['number']) || intval($_POST['number']) < 0){
                $this->end(false, '请输入自然数');
            }
        }
        
        #盘点_保质期物料
        if(empty($_POST['product_id']))
        {
            $this->end(false, '无商品信息');
        }
        
        #保质期物料检查是否有保质期信息
        $basicMConfObj    = app::get('material')->model('basic_material_conf');
        $basicMStorageLifeLib    = kernel::single('material_storagelife');
        $is_use_expire    = $basicMStorageLifeLib->checkStorageLifeById($_POST['product_id']);
        
        if ($is_use_expire)
        {
            $expire_bn_info    = json_decode($_POST['expire_bn_info'], true);
            if(empty($expire_bn_info))
            {
                $this->end(false, '没有保质期信息');
            }
            foreach ($expire_bn_info as $key => $val)
            {
                if(empty($val['bmsl_id']) || empty($val['in_num']))
                {
                    $this->end(false, '保质期信息无效');
                }
            }
            
            $_POST['is_use_expire']    = $is_use_expire;
            $_POST['expire_bn_info']   = $expire_bn_info;
        }
        
        $msg = '';
        $result = kernel::single('taoguaninventory_inventorylist')->save_inventory($_POST,$msg);
        if ($result){
            $msg = $msg!='' ? $msg : '盘点完成'; 

            $this->end(true, '盘点完成','index.php?app=wms&ctl=admin_inventory&act=go_inventory&inventory_id='.$inventory_id);
        }else {
            $msg    = ($msg != '' ? $msg : '盘点失败'); 
            $this->end(false, $msg);
        }

    }

    /*
    * 盘点明细
    */

    function detail_inventory_object(){
        $item_id = $_GET['item_id'];
        $product_id = $_GET['product_id'];
        $mdl_inventory_object = app::get('taoguaninventory')->model('inventory_object');
        $inventory_object_list =  $mdl_inventory_object->getList('oper_name,bn,barcode,pos_name,actual_num,oper_time', array('item_id'=>$item_id,'product_id'=>$product_id));
        $this->pagedata['items'] =$inventory_object_list;
        $this->page("admin/inventory/detail_inventory_object.html");
    }

    /*
    * 导出
    */
    function export_inventory(){
        set_time_limit(0);
        @ini_set('memory_limit','64M');
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=storange".date('Ymd').".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        $mdl_inventory = app::get('taoguaninventory')->model('inventory');
        $mdl_inventory_items = app::get('taoguaninventory')->model('inventory_items');
        $inventory_id = $_POST['inventory_id'];
        $shortage_over = $_POST['shortage_over'];
        $is_auto = $_POST['is_auto'];
        if($is_auto=='0' || $is_auto=='1'){
            $filter = array('inventory_id'=>$inventory_id,'is_auto'=>$is_auto);
        }else{
            $filter = array('inventory_id'=>$inventory_id);
        }
        $inventory_items = $mdl_inventory_items->getList('product_id, name,bn,spec_info,unit,accounts_num,price,actual_num,shortage_over', $filter);
        $title1 = $mdl_inventory->exportTemplate('shortage_over');
        echo '"'.implode('","',$title1).'"';
        echo "\n";
        
        $product_list    = array();
        $bm_ids          = array();
        
        foreach($inventory_items as $k=>$v){
            $v['name'] = kernel::single('base_charset')->utf2local($v['name']);
            //$v['bn'] = $v['bn']."\t";
            $v['bn'] = kernel::single('base_charset')->utf2local($v['bn'])."\t";
            $v['unit'] = kernel::single('base_charset')->utf2local($v['unit'])."\t";
            if($shortage_over==1){
               if($v['shortage_over']!=0){

                    echo '"'.implode('","',$v).'"'."\n";
                }
          }else{

               echo '"'.implode('","',$v).'"'."\n";
           }

           $bm_ids[]    = $v['product_id'];
           $product_list[$v['product_id']]    = $v['bn'];
        }
        
        #导出保质期物料明细
        if($bm_ids)
        {
            #查询所有保质期物料的盘点明细
            $oInventory_object       = app::get('taoguaninventory')->model('inventory_object');
            $basicMStorageLifeLib    = kernel::single('material_storagelife');
            $is_use_expire    = $basicMStorageLifeLib->checkStorageLifeById($bm_ids);
            
            if($is_use_expire)
            {
                #导出标题
                $title1    = $mdl_inventory->exportTemplate('expire');
                echo '"'.implode('","',$title1).'"';
                echo "\n";
                
                #导出列表明细
                $storage_life_list  = array();
                $filter             = array('inventory_id'=>$inventory_id, 'product_id'=>$bm_ids, 'storage_life_info|noequal'=>'');
                $oInventory_list    = $oInventory_object->getList('obj_id, item_id, bn, barcode, pos_name, product_id, storage_life_info', $filter);
                foreach ($oInventory_list as $key => $val)
                {
                    $storage_life_info    = unserialize($val['storage_life_info']);
                    foreach ($storage_life_info as $key_j => $val_j)
                    {
                        $export_row    = array();
                        $export_row['expire_bn']     = $val_j['expire_bn'];
                        
                        $val['bn']    = ($val['bn'] ? $val['bn'] : $product_list[$val['product_id']]);
                        $export_row['bn']            = $val['bn'];
                        
                        $export_row['barcode']       = $val['barcode'];
                        $export_row['pos_name']      = $val['pos_name'];
                        $export_row['in_num']        = $val_j['in_num'];
                        
                        echo '"'.implode('","', $export_row).'"'."\n";
                    }
                }
            }
        }
    }

    /*
    * 盘点编辑
    *
    */
    function edit_inventory(){
        $inventory_id = intval($_GET['inventory_id']);
        $oInventory_items = app::get('taoguaninventory')->model('inventory_items');
        $oinventory_object = app::get('taoguaninventory')->model('inventory_object');
        $oInventory = app::get('taoguaninventory')->model('inventory');

        $basicMStorageLifeLib    = kernel::single('material_storagelife');
        
        $inventory = $oInventory->dump($inventory_id,'branch_id,inventory_name,inventory_bn,branch_name,inventory_type,difference,inventory_checker,pos,second_checker,finance_dept,warehousing_dept,add_time,inventory_date,branch_name');
        $inventory_items = $oInventory_items->getList('*', array('inventory_id'=>$inventory_id,'is_auto'=>'0'));
        foreach($inventory_items as $k=>$v){
            $inventory_items[$k]['PRIMARY_ID']=$v['product_id'];
            $item=$oinventory_object->getlist('*',array('inventory_id'=>$v['inventory_id'],'product_id'=>$v['product_id']));
            foreach($item as $key=>$val){
                $item[$key]['PRIMARY_ID']=$val['obj_id'].$val['item_id'];
                $item[$key]['oper_time']=date('Y-m-d',$val['oper_time']);
            }
            $inventory_items[$k]['item']= $item;
            
            #是否有保质期物料
            $inventory_items[$k]['is_use_expire']    = $basicMStorageLifeLib->checkStorageLifeById($v['product_id']);
        }
        
        $branch_product = kernel::single('taoguaninventory_inventorylist')->check_product_iostock($inventory['branch_id']);
        $this->pagedata['inventory_items'] = $inventory_items;
        $this->pagedata['branch_product'] = $branch_product;
        $this->pagedata['inventory_id'] = $inventory_id;
        $this->pagedata['inventory'] = $inventory;

        $this->singlepage('admin/inventory/edit_inventory.html');
    }


    function findProduct($supplier_id=null)
    {
        $base_filter    = array();
        
        $base_filter['visibled']    = 1;//过滤隐藏的商品
        
        $params = array(
                'title'=>'基础物料列表',
                'use_buildin_new_dialog' => false,
                'use_buildin_set_tag'=>false,
                'use_buildin_recycle'=>false,
                'use_buildin_export'=>false,
                'use_buildin_import'=>false,
                'use_buildin_filter'=>true,
                'base_filter' => $base_filter,
        );
        $this->finder('material_mdl_basic_material', $params);
   }

    function getProducts()
    {
        $basicMaterialSelect    = kernel::single('material_basic_select');
        $basicMaterialBarcode    = kernel::single('material_basic_material_barcode');
        
        $pro_id = $_POST['product_id'];
        $pro_bn= $_GET['bn'];
        $pro_name= $_GET['name'];
        $pro_barcode= $_GET['barcode'];
        
        if (is_array($pro_id)){
            $filter['bm_id'] = $pro_id;
        }
        if($pro_bn){
           $filter = array(
               'material_bn'=>$pro_bn
           );
        }
        
        if($pro_name){
            $filter = array(
               'material_name'=>$pro_name
           );
        }
        
        if($pro_barcode)
        {
            /*
            $filter = array(
               'barcode'=>$pro_barcode
           );
           */
            #查询条形码对应的bm_id
            $bm_ids    = $basicMaterialBarcode->getBmidListByBarcode($pro_barcode);
            $filter = array('bm_id'=>$bm_ids);
        }
        
        if($_GET['branch_id'])
        {
            $branch_id = $_GET['branch_id'];
        }
        
        $data    = $basicMaterialSelect->getlist_ext('bm_id, material_bn, material_name, visibled, retail_price, specifications', $filter);
        
        if (!empty($data))
        {
            foreach ($data as $k => $item)
            {
                #查询关联的条形码
                $item['barcode']    = $basicMaterialBarcode->getBarcodeById($item['product_id']);
                
                $item['num'] = 0;
                
                if (!$item['price']){
                    $data[$k]['price'] = 0;
                }
                if($branch_id){
                    $data[$k]['accounts_num'] = kernel::single('taoguaninventory_inventorylist')->get_accounts_num($item['product_id'],$branch_id);
                    $data[$k]['pos_name'] = '';
                }
                $data[$k]['PRIMARY_ID'] = $item['product_id'];

            }
        }

        $rows = $data;
        echo "window.autocompleter_json=".json_encode($rows);
    }

    /*
    *返回商品json结果
    *@return json
    */
    function getProduct()
    {
        $basicMaterialSelect    = kernel::single('material_basic_select');
        $basicMaterialBarcode    = kernel::single('material_basic_material_barcode');

        $product = array();
        $searchtype = $_GET['searchtype'];
        $product_bn = $_GET['product_bn'];

        if($searchtype=='bn'){
            $filter = array(
                'material_bn' => $product_bn
            );
        }elseif($searchtype=='barcode'){
            #查询条形码对应的bm_id
            $bm_ids    = $basicMaterialBarcode->getBmidListByBarcode($product_bn);
            $filter = array('bm_id'=>$bm_ids);
        }
        
        $data    = $basicMaterialSelect->getlist_ext('bm_id, material_bn, material_name, visibled, retail_price, specifications', $filter);
        if($data){
            foreach($data as $k=>$v)
            {
                #查询关联的条形码
                $data[$k]['barcode']    = $basicMaterialBarcode->getBarcodeById($v['product_id']);
                $data[$k]['PRIMARY_ID'] = $data['product_id'];
            }

            $product= json_encode($data);
            echo $product;
        }
    }

     function getEditProducts($inventory_id){
        if ($inventory_id == ''){
            $inventory_id = $_POST['p[0]'];
        }

        $filter['inventory_id']=$inventory_id;
        $filter['is_auto']='0';
        $searchtype = $_GET['searchtype'];
        $product_bn = $_GET['product_bn'];
        if($searchtype){
            if($searchtype=='bn'){
                $filter['bn'] = $product_bn;
            }else if($searchtype=='barcode'){
                $filter['barcode'] = $product_bn;
            }
        }

        $oInventory_items = app::get('taoguaninventory')->model('inventory_items');
        $oinventory_object = app::get('taoguaninventory')->model('inventory_object');
        $oInventory = app::get('taoguaninventory')->model('inventory');
        $basicMStorageLifeLib    = kernel::single('material_storagelife');

        $rows = array();
        $items = $oInventory_items->getList('product_id,bn,name,spec_info,unit,pos_name,accounts_num,actual_num,shortage_over,price,inventory_id,item_id,accounts_num',$filter,0,-1);
        foreach($items as $k=>$v){
            $items[$k]['PRIMARY_ID'] = $v['product_id'];
            
            #是否有保质期物料
            $is_use_expire        = $basicMStorageLifeLib->checkStorageLifeById($v['product_id']);
            $items[$k]['is_use_expire']    = ($is_use_expire ? 'true' : 'false');
            $items[$k]['expire_msg']       = ($is_use_expire ? '是' : '否');
            
            $storage_life_list    = array();
            
            #明细
            $item = $oinventory_object->getlist('pos_id,item_id,product_id,bn,barcode,obj_id,oper_name,oper_time,pos_name,actual_num, storage_life_info',array('inventory_id'=>$v['inventory_id'],'product_id'=>$v['product_id']));
            foreach($item as $key=>$val)
            {
                $item[$key]['PRIMARY_ID'] = $val['obj_id'].$val['item_id'];
                $item[$key]['oper_time'] = date('Y-m-d',$val['oper_time']);
                
                #保质期物料盘点
                if($is_use_expire)
                {
                    $storage_life_info    = unserialize($val['storage_life_info']);
                    foreach ($storage_life_info as $key_j => $val_j)
                    {
                        unset($val['storage_life_info']);#销毁
                        
                        $val_j['PRIMARY_ID']   = $val['obj_id'].$val['item_id'];
                        $val_j['oper_time']    = date('Y-m-d',$val['oper_time']);
                        $storage_life_list[]    = array_merge($val, $val_j);
                    }
                }
            }
            $items[$k] ['item']= ($is_use_expire ? $storage_life_list : $item);
        }
        
        if ($items)
        $rows = $items;
        echo json_encode($rows);
    }

    /**
    *编辑盘点单基本信息
    */
    function doEditbasic(){
        $oInventory = app::get('taoguaninventory')->model('inventory');
        $data = $_POST;
        $this->begin();
        $branch_id = $data['branch_id'];
        $inventory_data['inventory_type'] = $data['inventory_type'];
        $inventory_data['inventory_name'] = $data['inventory_name'];
        $inventory_data['inventory_id'] = $data['inventory_id'];
         if($data['inventory_type']==2){
            $inv_exist = $oInventory->dump(array('branch_id'=>$branch_id,'inventory_type'=>2,'confirm_status'=>1),'inventory_id');

            if($inv_exist['inventory_id']!=$data['inventory_id'] && $inv_exist){
                $this->end(false,'此仓库已有全盘的盘点单存在');
            }
            $inv_exist1 = $oInventory->dump(array('branch_id'=>$branch_id,'inventory_type'=>3,'confirm_status'=>1),'inventory_id');

             if($inv_exist1['inventory_id']!=$data['inventory_id'] && $inv_exist1){
                $this->end(false,'请将部分盘点确认后再新建全盘');
            }
        }
        if($inventory_data['inventory_type']==3){
             $inv_exist2 = $oInventory->dump(array('branch_id'=>$branch_id,'inventory_type'=>2,'confirm_status'=>1),'inventory_id');
             if($inv_exist2['inventory_id']!=$data['inventory_id'] && $inv_exist2){
                $this->end(false,'请将此仓库全盘确认后再新建部分盘点');
            }
        }

        $result=$oInventory->save($inventory_data);
        kernel::single('taoguaninventory_inventorylist')->hide_add_product_list($data['inventory_id'],$data['inventory_type'],$data['branch_id']);
        $this->end(true, '修改成功','index.php?app=wms&ctl=admin_inventory&act=index&flt=confirm');
    }
    /*
    *编辑盘点单
    *
    */
    function doEdit(){
        $data = $_POST;

        $this->begin();
        $oInventory_items = app::get('taoguaninventory')->model('inventory_items');
        $oInventory_object = app::get('taoguaninventory')->model('inventory_object');
        $oInventory = app::get('taoguaninventory')->model('inventory');
        $opObj  = app::get('ome')->model('operation_log');

        $basicMStorageLifeLib    = kernel::single('material_storagelife');
        
        if(is_array($data['product_id'] ))
        {
            #查询所有保质期物料的盘点明细
            $is_use_expire    = $basicMStorageLifeLib->checkStorageLifeById($data['product_id']);
            if($is_use_expire)
            {
                $storage_life_list  = array();
                $filter             = array('inventory_id'=>$data['inventory_id'], 'product_id'=>$data['product_id'], 'storage_life_info|noequal'=>'');
                $oInventory_list    = $oInventory_object->getList('obj_id, item_id, product_id, storage_life_info', $filter);
                foreach ($oInventory_list as $key => $val)
                {
                    $storage_life_info    = unserialize($val['storage_life_info']);
                    foreach ($storage_life_info as $key_j => $val_j)
                    {
                        $oInventory_list[$val['obj_id']][$val_j['bmsl_id']]    = $val_j;
                    }
                }
            }
            
        foreach($data['product_id'] as $k=>$v){

            $inventory =  array(
                    'inventory_id'=>$data['inventory_id'],
                    'product_id'=>$v,
                    'branch_id'=>$data['branch_id'],
                    //'pos_id'=>$data['pos_id'][$k],
            );
            $inv_item = $oInventory_items->dump(array('inventory_id'=>$data['inventory_id'],'product_id'=>$v),'item_id,actual_num');

            $inventory['item_id'] = $inv_item['item_id'];
            
            #查询物料是否保质期物料
            $is_use_expire    = $basicMStorageLifeLib->checkStorageLifeById($v);
            
            #保质期盘点保存
            if($is_use_expire)
            {
                if($data['cname'][$v]){
                    foreach($data['cname'][$v] as $obj_id => $obj_item)
                    {
                        $count_num    = 0;
                        $storage_life_list    = array();
                        
                        foreach ($obj_item as $bmsl_id => $val_num)
                        {
                            $oInventory_list[$obj_id][$bmsl_id]['in_num']    = $val_num;#修改的数量
                            $storage_life_list[]    = $oInventory_list[$obj_id][$bmsl_id];
                            
                            $count_num    += $val_num;
                        }
                        
                        #保存
                        $inventory['expire_bn_info'] = $storage_life_list;
                        
                        $inventory['number'] = $count_num;
                        $inventory['obj_id'] = $obj_id;
                        $inventory['item_id'] = $inv_item['item_id'];
                        $inventory['pos_id'] = $data['pos_id'][$v][$obj_id];
                        $inventory['pos_name'] = $data['pos_name'][$v][$obj_id];
                        
                        kernel::single('taoguaninventory_inventorylist')->update_inventory_item($inventory);
                    }
                }
            }
            else 
            {
                if($data['cname'][$v]){
                    foreach($data['cname'][$v] as $key=>$val){
                        $inventory['number'] = $val;
                        $inventory['obj_id'] = $key;
                        $inventory['item_id'] = $inv_item['item_id'];
                        $inventory['pos_id'] = $data['pos_id'][$v][$key];
                        $inventory['pos_name'] = $data['pos_name'][$v][$key];
                        kernel::single('taoguaninventory_inventorylist')->update_inventory_item($inventory);
                    }
                }
            }
            
            if($data['pname'][$v]){
                foreach($data['pname'][$v] as $pkey=>$pval){

                    $inventory['number'] = $pval;
                    if($data['ppos_id'][$v]){
                        $inventory['pos_name'] = $data['ppos_id'][$v][$pkey];
                    }
                    unset($inventory['obj_id']);
                    unset($inventory['pos_id']);
                    $inventory['item_id'] = $inv_item['item_id'];

                    kernel::single('taoguaninventory_inventorylist')->update_inventory_item($inventory);
                }
            }
        }
            kernel::single('taoguaninventory_inventorylist')->update_inventorydifference($data['inventory_id']);

            $opObj->write_log('inventory_modify@taoguaninventory', $data['inventory_id'], '盘点单编辑');
            $this->end(true, '盘点修改完成','app=wms&ctl=admin_inventory&act=index&flt=confirm');
        }else{

            $this->end(false, '您没有添加任何盘点商品','index.php?app=wms&ctl=admin_inventory&act=edit_inventory&inventory_id='.$data['inventory_id'].'');
        }

    }

    /**
     * @删除盘点表明细
     * @access public
     * @param void
     * @return void
     */
    public function del_inventory(){
        $action = $_GET['action'];
        if($action=='del_obj_id')
        {
             $del_obj_data  = array(
                                    'action'=>'obj',
                                    'obj_id'=>$_GET['obj_id'],
                                    'inventory_id'=>$_GET['inventory_id'],
                                     'bmsl_id' => $_GET['bmsl_id'],
                                    );

            $result = kernel::single('taoguaninventory_inventorylist')->del_inventory($del_obj_data);
        }
        if($action=='del_item_id'){
            $del_item_data =array(
                                'action'=>'item',
                                'item_id'=>$_GET['item_id'],
                                'inventory_id'=>$_GET['inventory_id'],
                            );
            $result = kernel::single('taoguaninventory_inventorylist')->del_inventory($del_item_data);
        }
        $data = array();
         if($result){
             $data['message'] = '删除成功';
         } else {
             $data['message'] = '删除成功';
         }
         echo json_encode($data);
    }

    /**
    *  预盈亏计算并显示
    */
    function shortage_over($inventory_id=null,$page=1){
        $data = $_GET;
        $page = intval($_GET['page']);
        $page = $page ? $page : 1;

        $pagelimit = 10;
        $inventory_id = $data['inventory_id'];
        $branch_id = $data['branch_id'];
        $oInventory = app::get('taoguaninventory')->model('inventory');
        $oInventory_items = app::get('taoguaninventory')->model('inventory_items');
        if($branch_id){
            kernel::single('taoguaninventory_inventorylist')->refresh_shortage_over($inventory_id,$branch_id);
        }
        $inventory_items = $oInventory_items->getList('product_id,bn,name,spec_info,unit,pos_name,accounts_num,actual_num,shortage_over,price,inventory_id,item_id,accounts_num,is_auto',array('inventory_id'=>$inventory_id),$pagelimit*($page-1), $pagelimit,'is_auto asc,bn desc');
        $total = $oInventory->getInventoryTotal($inventory_id);

        if ($inventory_items){
            foreach ($inventory_items as $k=>$v){
                 //小计
                 $subtotal['accounts_num'] += $v['accounts_num'];
                 $subtotal['actual_num'] += $v['actual_num'];
                 $subtotal['shortage_over'] += $v['shortage_over'];
            }
        }
        $count = $total['count'];
        $pager = $this->ui()->pager(array(
            'current'=>$page,
            'total'=>ceil($count/$pagelimit),
            'link'=>'index.php?app=wms&ctl=admin_inventory&act=shortage_over&inventory_id='.$inventory_id.'&page=%d',
        ));
        $this->pagedata['inventory_item'] = $inventory_items;
        $this->pagedata['subtotal'] = $subtotal;#小计
        $this->pagedata['total'] = $total[0];#总计
        $this->pagedata['pager'] = $pager;
        $this->pagedata['count'] = $count;
        $this->pagedata['pagelimit'] = $pagelimit;
        $this->pagedata['inventory_id'] = $inventory_id;
        unset($inventory_item);
        $this->page('admin/inventory/shortage_over.html');
    }

    /**
    * 刷新预盈亏
    */
    function refresh_shortage_over(){
        $this->begin();
        $inventory_id = $_GET['inventory_id'];
        $branch_id = $_GET['branch_id'];
        $opObj  = app::get('ome')->model('operation_log');
        $result = kernel::single('taoguaninventory_inventorylist')->refresh_shortage_over($inventory_id,$branch_id);
        $opObj->write_log('inventory@taoguaninventory', $inventory_id, '盘点单刷新预盈亏');
        $this->end(true, '成功');
    }

    /**
    * 确认盘点
    */
    function confirm_inventory($inventory_id,$page = 0){
        $inventory_id = intval($inventory_id);
        $page = intval($page);
        $page = $page ? $page : 1;
        $pagelimit = 8;
        $oInventory = app::get('taoguaninventory')->model('inventory');
        $oInventory_items = app::get('taoguaninventory')->model('inventory_items');
        $inventory_detail = $oInventory->dump($inventory_id, '*');
        $is_auto = $_GET['is_auto'];
        if(($is_auto=='0') || ($is_auto=='1')){

            $filter = array('inventory_id'=>$inventory_id,'is_auto'=>$is_auto);
            $total = $oInventory->getInventoryTotal($inventory_id,$is_auto);
        }else{
            $filter = array('inventory_id'=>$inventory_id);
            $total = $oInventory->getInventoryTotal($inventory_id);
        }


        //盘点明细
       $inventory_items = $oInventory_items->getlist('name,bn,spec_info,item_id,unit,price,memo,actual_num,shortage_over,accounts_num,product_id,is_auto',$filter, $pagelimit*($page-1), $pagelimit,'is_auto ASC,bn DESC');
       $branch_id =  $inventory_detail['branch_id'];

       $basicMStorageLifeLib    = kernel::single('material_storagelife');
       
       $total_price = 0;
       #盈亏总金额
       $total_shortage_over_price = 0;
        if ($inventory_items)
        foreach ($inventory_items as $k=>$v){
            #保质期增加filter条件
            $accounts_num = kernel::single('taoguaninventory_inventorylist')->get_accounts_num($v['product_id'],$branch_id, $inventory_id, $v['item_id']);
            
            #成本价
            $price = kernel::single('taoguaninventory_inventorylist')->get_price($v['product_id'],$branch_id);
            $total_price += $price;
            $inventory_items[$k]['price'] = $price;
            #盈亏数量
            $shortage_over = $v['actual_num']-$accounts_num;
            #盈亏金额
            $shortage_over_price = $price * $shortage_over;
            $inventory_items[$k]['shortage_over_price'] = $shortage_over_price;
            $total_shortage_over_price  += $shortage_over_price;
            
            $inventory_items[$k]['accounts_num'] = $accounts_num;
            $inventory_items[$k]['shortage_over'] = $shortage_over;
             //小计
             $subtotal['accounts_num'] += $accounts_num;
             $subtotal['actual_num'] += $v['actual_num'];
             $subtotal['shortage_over'] += $shortage_over;
             
             #是否有保质期物料
             $inventory_items[$k]['is_use_expire']    = $basicMStorageLifeLib->checkStorageLifeById($v['product_id']);
        }
        $total[0]['price'] = $total_price;
        $total[0]['shortage_over_price'] = $total_shortage_over_price;
        $count = $total['count'];

        $pager = $this->ui()->pager(array(
            'current'=>$page,
            'total'=>ceil($count/$pagelimit),
            'link'=>'index.php?app=wms&ctl=admin_inventory&act=confirm_inventory&is_auto='.$is_auto.'&p[0]='.$inventory_id.'&p[1]=%d',
        ));
        
        $this->pagedata['pager'] = $pager;
        $this->pagedata['is_auto'] = $is_auto;
        $this->pagedata['detail'] = $inventory_detail;
        $this->pagedata['items'] = $inventory_items;
        $this->pagedata['inventory_id'] = $inventory_id;
        $this->pagedata['total'] = $total[0];#小计
        //$this->pagedata['find_id'] = $_GET['find_id'];
        $this->pagedata['count'] = $count;
        
        $this->pagedata['pagelimit'] = $pagelimit;
        $this->pagedata['view'] = $_GET['view'];
        #需确认条数
        $need_inventoryList = kernel::single('taoguaninventory_inventorylist')->ajax_inventorylist($inventory_id);
        
        $this->pagedata['need_inventoryList'] = json_encode($need_inventoryList);
        $this->pagedata['need_inventorylist_count'] = count($need_inventoryList);
        $this->page('admin/inventory/confirm_inventory.html');
    }

   
    /**
    * 导入
    */
    function import(){
        $this->page('admin/inventory/import.html');
    }

    function batch_cancel(){
        $this->begin();
        $inventory_id = $_POST['inventory_id'];
        $inventoryObj = app::get('taoguaninventory')->model('inventory');
        if( is_array($inventory_id) ){
            foreach($inventory_id as $k=>$v){
                $inventory = $inventoryObj->dump(array('inventory_id'=>$v),'confirm_status');
                if($inventory['confirm_status']==2 || $inventory['confirm_status']==3){
                    $this->end(false, '不可以操作,请确认盘点单是否已确认或已作废');
                }
             }
             $inventoryObj->dead_inventory($inventory_id);
         }
         $this->end(true, '操作成功','javascript:finderGroup["'.$_GET['finder_id'].'"].unselectAll();finderGroup["'.$_GET['finder_id'].'"].refresh();');


    }

     /**
    *新建盘点
    */
    function inventory_selectbranch(){
        $brObj = app::get('ome')->model('branch');
        $branch_mode = app::get('ome')->getConf('ome.branch.mode');
        $is_super = kernel::single('desktop_user')->is_super();
        $branch_ids = kernel::single('wms_branch')->getBranchwmsByUser($is_super);
        
        $branch_list = $brObj->getList('branch_id,name',array('branch_id'=>$branch_ids),0,-1);
        $this->pagedata['date']         = date("Y年m月d日");
        $this->pagedata['op_name']      = kernel::single('desktop_user')->get_name();
        $this->pagedata['branch_list'] = $branch_list;
        $this->page("admin/inventory/add_inventory.html");
    }

    /**
    *批量删除
    */
    function batch_delete(){
        $this->begin();
        $inventory_id = $_POST['inventory_id'];
        $inventoryObj = app::get('taoguaninventory')->model('inventory');
        if( is_array($inventory_id) ){
            foreach($inventory_id as $k=>$v){
                $inventory = $inventoryObj->dump(array('inventory_id'=>$v),'confirm_status');
                if($inventory['confirm_status']==2 || $inventory['confirm_status']==4 ){
                    $this->end(false, '盘点单已确认或确认中，不可删除');
                }
             }
             $inventoryObj->batch_delete($inventory_id);
         }
         $this->end(true, '操作成功','javascript:finderGroup["'.$_GET['finder_id'].'"].unselectAll();finderGroup["'.$_GET['finder_id'].'"].refresh();');


    }

    
    /**
    * 过滤盘点条件
    */
    function filter_inventory(){
        $inventory_id = intval($_POST['inventory_id']);
        $oInventory = app::get('taoguaninventory')->model('inventory');
        $inventory = $oInventory->dump(array('inventory_id'=>$inventory_id),'confirm_status,inventory_id');
        if ($inventory['confirm_status'] != 1 && $inventory['confirm_status']!=4) {
            $data = array(
                'message' => '盘点单已经确认或作废,不可以盘点',
                'result' => 'fail',
            );
        }else{
            $data = array(
                'result' => 'succ',
            );
        }
        echo json_encode($data);exit;
    }

    function export(){
        //获取操作员管辖仓库
        $oBranch = app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        $branch_ids = kernel::single('wms_branch')->getBranchwmsByUser($is_super);
        $branch_list   = $oBranch->getList('branch_id, name',array('branch_id'=>$branch_ids),0,-1);
        $this->pagedata['branch_list']   = $branch_list;
        $this->pagedata['is_super']   = $is_super;

        $this->pagedata['op_name'] = kernel::single('desktop_user')->get_name();
        $this->pagedata['inventory_name'] = date('m月d日',time()).'盘点表';
        $this->page("admin/inventory/export.html");
    }

    public function exportOne() {
        $inventory_id = (int) $_GET['inventory_id'];
        $data = app::get('wms')->model('inventory')->db_dump(['inventory_id'=>$inventory_id]);
        $this->pagedata['data'] = $data;
        $this->display("admin/inventory/export_one.html");
    }

    /*
    * 盘点导出，Json数据返回，为解决避免一次性抛出大量数据而改用动态翻页方法
    */
    function inventoryPreview($page=1){
        $data = $_POST;
        $page = $page ? $page : 1;
        $pagelimit = 12;
        $data['branch_id'] = $_POST['branch_id'];
        //读取仓库的货品信息
        $oInventory = app::get('taoguaninventory')->model('inventory');
        $export_type = $data['export_type'];
        //getPosList
        if($export_type==1){
            $inventory_detail = $oInventory->getProduct($data, $pagelimit*($page-1), $pagelimit);
        }else{
            $inventory_detail = $oInventory->getPosList($data, $pagelimit*($page-1), $pagelimit);
        }
        $count = $inventory_detail['count'];

        $total_page = ceil($count/$pagelimit);
        $pager = $this->ui()->pager(array(
            'current'=>$page,
            'total'=>$total_page,
            'link'=>'?page=%d'
        ));
        $this->pagedata['pager'] = $pager;
        unset($inventory_detail['count']);
        $this->pagedata['inventory'] = $inventory_detail;
        $this->pagedata['total_page'] = $total_page;
        $this->pagedata['pagelimit'] = $pagelimit;
        $this->pagedata['count'] = $count;
        $this->pagedata['cur_page'] = $page;
        return $this->display("admin/inventory/inventory_items_div.html");
    }

    /**
    *
    */
    function ajaxDoConfirminventory(){
        set_time_limit(0);
        $data = $_POST;
        $ajaxParams = trim($data['ajaxParams']);
        if (strpos($ajaxParams, ';')) {
            $params = explode(';', $ajaxParams);
        }else{
            $params = array($ajaxParams);
        }

        $inventory_id = $data['inventory_id'];
        $fail = 0;
        $succ=0;
        $fallinfo = array();
        $result = kernel::single('wms_inventory')->doajax_inventorylist($data,$params,$fail,$succ,$fallinfo);
        
        echo json_encode(array('total' => count($params), 'succ' => $succ, 'fail' => $fail, 'fallinfo'=>$fallinfo));
    }
    
    /**
     * 搜索保质期物料列表
     *
     */
    function search_storage_life()
    {
        set_time_limit(0);
        
        $page = intval($_GET['page']);
        $page = $page ? $page : 1;
        $pagelimit = 15;
        
        $inventory_id    = $_REQUEST['inventory_id'];
        $barcode      = trim($_REQUEST['barcode']);
        $branch_id    = $_REQUEST['branch_id'];
        $selecttype   = $_REQUEST['selecttype'];
        
        if(empty($barcode) || empty($branch_id) || empty($selecttype))
        {
            die('无效操作，请检查！');
        }
        
        #获取基础物料
        $ivObj    = app::get('taoguaninventory')->model('inventory');
        if($selecttype=='barcode')
        {
            $data = $ivObj->getProductbybarcode($branch_id, $barcode);
        }
        else if($selecttype=='bn')
        {
            $data = $ivObj->getProductbybn($branch_id, $barcode);
        }
        if (empty($data))
        {
            die('没有找到相关记录');
        }
        $bm_id    = $data['bm_id'];
        
        #盘点保质期明细
        $oInventory   = app::get('taoguaninventory')->model('inventory');
        $inventory    = $oInventory->dump($inventory_id, 'inventory_type');
        
        $filter    = array('inventory_id'=>$inventory_id, 'product_id'=>$bm_id);
        if($inventory['inventory_type'] == '2')
        {
            $filter['pos_id|than'] = 0;#全盘_货位模式
        }
        
        $oinventory_object  = app::get('taoguaninventory')->model('inventory_object');
        $objItemlist        = $oinventory_object->getlist('obj_id, item_id, storage_life_info', $filter);
        if($objItemlist)
        {
            $storage_life_list    = array();
            foreach ($objItemlist as $key => $val)
            {
                $storage_life_info    = unserialize($val['storage_life_info']);
                
                foreach ($storage_life_info as $key_j => $val_j)
                {
                    $storage_life_list[$val_j['bmsl_id']]    = $val_j['expire_bn'];
                }
            }
        }
        
        #高级搜索
        $search_expire_bn      = trim($_POST['search_expire_bn']);
        $expiring_date_from    = $_POST['expiring_date_from'];
        $expiring_date_to      = $_POST['expiring_date_to'];
        $production_date_from  = $_POST['production_date_from'];
        $production_date_to    = $_POST['production_date_to'];
        
        $filter    = array('bm_id'=>$bm_id, 'branch_id'=>$branch_id, 'status'=>1);
        
        if($search_expire_bn)
        {
            $filter['expire_bn|has']    = $search_expire_bn;
            
            $this->pagedata['search_expire_bn']    = $search_expire_bn;
        }
        if($expiring_date_from && $expiring_date_to)
        {
            $expiring_date_from    = strtotime($expiring_date_from);
            $expiring_date_to    = strtotime($expiring_date_to) + 86399;
            
            if($expiring_date_to > $expiring_date_from)
            {
                $filter['expiring_date|between']    = array($expiring_date_from, $expiring_date_to);
                
                $this->pagedata['search_expiring_date']    = array('time_from'=>$expiring_date_from, 'time_to'=>$expiring_date_to);
            }
        }
        if($production_date_from && $production_date_to)
        {
            $production_date_from    = strtotime($production_date_from);
            $production_date_to    = strtotime($production_date_to) + 86399;
        
            if($production_date_to > $production_date_from)
            {
                $filter['production_date|between']    = array($production_date_from, $production_date_to);
                
                $this->pagedata['search_production_date']    = array('time_from'=>$production_date_from, 'time_to'=>$production_date_to);
            }
        }
        
        #保质期批次号列表
        $basicMaterialStorageLifeObj    = app::get('material')->model('basic_material_storage_life');
        $storageLifeBatch    = $basicMaterialStorageLifeObj->getList('bmsl_id, expire_bn, production_date, expiring_date, in_num, balance_num', $filter, $pagelimit*($page-1), $pagelimit, 'expiring_date desc');
        
        #已存在的保质期禁止选择
        if($storage_life_list)
        {
            foreach ($storageLifeBatch as $key => $val)
            {
                $val['is_exist']    = ($storage_life_list[$val['bmsl_id']] ? true : false);
                
                $storageLifeBatch[$key]    = $val;
            }
        }
        
        $page_url    = 'index.php?app=wms&ctl=admin_inventory&act=search_storage_life&inventory_id='.$inventory_id.'&barcode='.$barcode.'&branch_id='.$branch_id.'&selecttype='.$selecttype;
        $count = $basicMaterialStorageLifeObj->count($filter);
        $pager = $this->ui()->pager(array(
                'current'=>$page,
                'total'=>ceil($count / $pagelimit),
                'link'=>$page_url.'&page=%d',
        ));
        $this->pagedata['pager'] = $pager;
        $this->pagedata['count'] = $count;
        $this->pagedata['pagelimit'] = $pagelimit;
        
        $this->pagedata['data']           = $storageLifeBatch;
        $this->pagedata['barcode']        = $barcode;
        $this->pagedata['selecttype']     = $selecttype;
        $this->pagedata['branch_id']      = $branch_id;
        $this->pagedata['bm_id']          = $bm_id;
        
        $this->page('admin/inventory/storage_life_list.html');
    }
    
    /**
     * 绑定保质期物料
     *
     */
    function bind_storage_life()
    {
        $bm_id        = $_POST['expire_bm_id'];
        $branch_id    = $_POST['expire_branch_id'];
        
        $bmsl_ids        = $_POST['bmsl_id'];
        $in_nums         = $_POST['in_nums'];
        $sel_expire_bn   = $_POST['sel_expire_bn'];
        
        if(empty($bm_id) || empty($bmsl_ids) || empty($in_nums))
        {
            die(json_encode(array('code' => 'error', 'msg' => '无效操作，请检查')));
        }
        
        $count             = 0;
        $expire_bn_list    = array();
        foreach ($bmsl_ids as $key => $val)
        {
            $num    = intval($in_nums[$val]);
            $bn     = $sel_expire_bn[$val];
            if($num < 0)
            {
                die(json_encode(array('code' => 'error', 'msg' => '保质期：'. $bn . '未输入有效盘点数量')));
            }
            
            $expire_bn_list[]    = array('bmsl_id'=>$val, 'expire_bn'=>$bn, 'in_num'=>$num);
            $count    += $num;
        }
        
        $basicMStorageLifeLib    = kernel::single('material_storagelife');
        
        #查询物料是否保质期物料
        $is_use_expire    = $basicMStorageLifeLib->checkStorageLifeById($bm_id);
        if(!$is_use_expire)
        {
            die(json_encode(array('code' => 'error', 'msg' => '基础物料不是保质期类型')));
        }
        
        $msg    = json_encode($expire_bn_list);
        echo json_encode(array('code' => 'succ', 'msg' => $msg, 'count'=>$count));
        exit;
    }
    
    /*
     * 展示关联保质期
     *
     */
    function show_storage_life()
    {
        $inventory_id    = $_POST['inventory_id'];
        $item_id         = $_POST['item_id'];
        $bm_id           = $_POST['bm_id'];
        
        if(empty($inventory_id) || empty($item_id) || empty($bm_id))
        {
            die('无效操作，请检查！');
        }
        
        $oInventory           = app::get('taoguaninventory')->model('inventory');
        $oInventory_object    = app::get('taoguaninventory')->model('inventory_object');
        $basicMaterialStorageLifeObj    = app::get('material')->model('basic_material_storage_life');
        
        $inventory    = $oInventory->dump($inventory_id, 'branch_id, confirm_status');
        
        $inventoryList        = $oInventory_object->getList('obj_id, product_id, storage_life_info', array('inventory_id'=>$inventory_id, 'item_id'=>$item_id, 'product_id'=>$bm_id));
        if(empty($inventoryList))
        {
            die('没有保质期批次详细信息');
        }
        
        $storage_life_list    = array();
        foreach ($inventoryList as $key => $val)
        {
            $storage_life_info    = unserialize($val['storage_life_info']);
            
            foreach ($storage_life_info as $key_j => $val_j)
            {
                #未确认盘点_实时读取保质期账面数量
                if($inventory['confirm_status'] != 2)
                {
                    $inv_filter		= array('branch_id'=>$inventory['branch_id'], 'product_id'=>$val['product_id'], 'expire_bn'=>$val_j['expire_bn']);
                    $storage_life_num	= $basicMaterialStorageLifeObj->dump($inv_filter, 'balance_num');
                    $val_j['account_num']    = $storage_life_num['balance_num'];
                }
                
                $storage_life_list[]    = $val_j;
            }
        }
        
        $this->pagedata['storage_life_list']    = $storage_life_list;
        
        $this->page('admin/inventory/show_life_list.html');
    }
}
?>
