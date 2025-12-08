<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_ctl_admin_iostockorder extends desktop_controller{
    var $name = "出入库管理";
    var $workground = "wms_center";

    /**
     * index
     * @return mixed 返回值
     */
    public function index() {}
    function allocate_iostock(){
        $io = $_GET['io'];
        $iostock_instance = kernel::single('siso_receipt_iostock');
        if($io){
            $title = '调拨入库';
            eval('$type='.get_class($iostock_instance).'::ALLOC_STORAGE;');
        }else{
            $title = '调拨出库';
            eval('$type='.get_class($iostock_instance).'::ALLOC_LIBRARY;');
        }

        $params = array(
            'title'=>$title,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'finder_cols'=>'column_confirm,name,iso_bn,oper,operator,original_bn,create_time,type_id',
        );
        /* 获取操作员管辖仓库 */
        $oBranch = app::get('ome')->model('branch');

        $is_super = kernel::single('desktop_user')->is_super();
        $branch_ids = kernel::single('wms_branch')->getBranchwmsByUser($is_super);
        #if (!$is_super){
            #$branch_ids = $oBranch->getBranchByUser(true);
        if ($branch_ids){
            $oIso = app::get('taoguaniostockorder')->model("iso");
            $iso_list = $oIso->getList('iso_id', array('branch_id'=>$branch_ids), 0,-1);
            if ($iso_list){
                foreach ($iso_list as $p){
                    $isolist[] = $p['iso_id'];
                }
            }
            if ($isolist){
                $isolist = array_unique($isolist);
                $params['base_filter']['iso_id'] = $isolist;
            }else{
                $params['base_filter']['iso_id'] = 'false';
            }
        }else{
            $params['base_filter']['iso_id'] = 'false';
        }
        #}

        $params['base_filter']['type_id'] = $type;
        $params['base_filter']['confirm'] = 'N';
        $params['base_filter']['check_status'] = '2';
        $this->finder('taoguaniostockorder_mdl_iso', $params);
    }
    /**
     * 
     * 其他入库列表
     */
    function other_iostock(){
        $io = $_GET['io'];
        if($io){
            $title = '其他入库';
            $this->name = "入库管理";
        }else{
            $title = '其他出库';
            $this->name = "出库管理";
        }

        $params = array(

            'title'=>$title,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'finder_cols'=>'column_confirm,name,iso_bn,oper,operator,original_bn,create_time,type_id',
        );
        /* 获取操作员管辖仓库 */
        $oBranch = app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        $is_super = kernel::single('desktop_user')->is_super();
        $branch_ids = kernel::single('wms_branch')->getBranchwmsByUser($is_super);
        #if (!$is_super){
            #$branch_ids = $oBranch->getBranchByUser(true);
        if ($branch_ids){
            $oIso = app::get('taoguaniostockorder')->model("iso");
            $iso_list = $oIso->getList('iso_id', array('branch_id'=>$branch_ids), 0,-1);
            if ($iso_list){
                foreach ($iso_list as $p){
                    $isolist[] = $p['iso_id'];
                }
            }
            if ($isolist){
                $isolist = array_unique($isolist);
                $params['base_filter']['iso_id'] = $isolist;
            }else{
                $params['base_filter']['iso_id'] = 'false';
            }
        }else{
            $params['base_filter']['iso_id'] = 'false';
        }
        #}

        $params['base_filter']['type_id'] = kernel::single('wms_iostockorder')->get_create_iso_type($io,true);
        $params['base_filter']['confirm'] = 'N';
        $params['base_filter']['check_status'] = '2';
        $this->finder('taoguaniostockorder_mdl_iso', $params);
    }



    function iostockorder_confirm($iso_id,$io)
    {
        $basicMaterialBarcode    = kernel::single('material_basic_material_barcode');
        $libBranchProductPos    = kernel::single('ome_branch_product_pos');
        $basicMStorageLifeLib    = kernel::single('material_storagelife');
        
        $oIso = app::get('taoguaniostockorder')->model("iso");
        $oIsoItems = app::get('taoguaniostockorder')->model("iso_items");
        
        $count = count($oIsoItems->getList('*',array('iso_id'=>$iso_id), 0, -1));
        $iso_items = $oIsoItems->getList('*',array('iso_id'=>$iso_id));
        $iso = $oIso->dump($iso_id,'branch_id,supplier_id,type_id');
        foreach($iso_items as $k=>$v)
        {
            $barcode_val    = $basicMaterialBarcode->getBarcodeById($v['product_id']);
            
            $iso_items[$k]['barcode'] = $barcode_val;
            $assign = $libBranchProductPos->get_pos($v['product_id'],$iso['branch_id']);
            if(empty($assign)){
                $iso_items[$k]['is_new']="true";
            }else{
                $iso_items[$k]['is_new']="false";
            }
            $iso_items[$k]['spec_info'] = $v['spec_info'];
            $iso_items[$k]['entry_num'] = (isset($v['in_num']) ? $v['nums'] - $v['in_num'] : $v['nums']);#可调拨数量
            $iso_items[$k]['in_num']    = intval($v['in_num']);#保质期_已入库数量
            
            //[开启]保质期监控
            $get_material_conf    = $basicMStorageLifeLib->checkStorageLifeById($v['product_id']);
            if($get_material_conf)
            {
                $iso_items[$k]['use_expire'] = 1;
            }
        }
        
        $this->pagedata['operator'] = kernel::single('desktop_user')->get_name();
        $this->pagedata['iso_items'] = $iso_items;
        $this->pagedata['iso_id'] = $iso_id;
        $this->pagedata['count']=$count;
        $this->pagedata['branch_id']=$iso['branch_id'];
        $this->pagedata['type_id'] = $iso['type_id'];
        $this->pagedata['io'] = $io;
        if($io){
            $this->singlepage("admin/iostock/instock_confirm.html");
        }else{
            $this->singlepage("admin/iostock/outstock_confirm.html");
        }

    }

    /**
     * 出入库确认
     */
    function save_iso_confirm(){
        $this->begin('index.php?app=wms&ctl=admin_iostockorder');
        $oIsoItems = app::get('taoguaniostockorder')->model("iso_items");
        $oIso = app::get('taoguaniostockorder')->model("iso");
        $oBranch_pos = app::get('ome')->model("branch_pos");
        
        $entry_num = $_POST['entry_num'];
        $iso_id = $_POST['iso_id'];
        $ids = $_POST['ids'];
        $branch_id = $_POST['branch_id'];
        $msg = '';

        $Iso = $oIso->dump(array('iso_id'=>$iso_id),'confirm,type_id,iso_bn');
        if ($Iso['type_id']=='5' || $Iso['type_id']=='50'){
            $branch_detail = kernel::single('wms_iostockdata')->getBranchByid($branch_id);
            if ($branch_detail['type']!='damaged'){
                $this->end(false, '出入库类型为残损入库，仓库必须为残仓');
            }
        }

        $iso_items = $oIsoItems->getList('*',array('iso_id'=>$iso_id));

        $io = $_POST['io'];
        
        #关联保质期物料
        $basicMaterialObj     = app::get('material')->model('basic_material');
        $basicMaterialConf    = app::get('material')->model('basic_material_conf');
        $basicMReceiptStorageLifeLib = kernel::single('material_receipt_storagelife');
        $basicMStorageLifeLib    = kernel::single('material_storagelife');
        
        $expire_bm_ids = $_POST['is_expire_bn'];
        $expire_bm_arr = $_POST['expire_bm_info'];
        $back_url    = 'index.php?app=wms&ctl=admin_iostockorder&act=iostockorder_confirm&p[0]='.$iso_id.'&p[1]='.$io;
        
        if($io){
            $label = '入库';

            //有保质期物料数据处理
            $all_expire_bm_arr = array();
            $has_expire_bn = false;
            if($expire_bm_ids){
                $has_expire_bn = true;
                if($expire_bm_arr){
                    foreach($expire_bm_arr as $expire_bm){
                        $tmp_expire_bm_arr = array();
                        $tmp_expire_bm_arr = json_decode($expire_bm,true);
                        if($tmp_expire_bm_arr){
                            foreach($tmp_expire_bm_arr as $k => $tmp_expire_bm){
                                //如果保质期已存在，判断是否有效状态可操作
                                $storageLifeInfo = $basicMStorageLifeLib->getStorageLifeBatch($branch_id, $tmp_expire_bm['bm_id'], $tmp_expire_bm['expire_bn']);
                                if($storageLifeInfo){
                                    if($storageLifeInfo['status'] == 2){
                                        $this->end(false, '保质期条码已被关闭停用：'.$tmp_expire_bm['expire_bn'], $back_url);
                                    }
                                }

                                $basicMInfo = $basicMaterialObj->dump(array('bm_id'=>$tmp_expire_bm['bm_id']), 'bm_id, material_name, material_bn,material_bn_crc32');
                                $basicMaterialConfInfo = $basicMaterialConf->dump(array('bm_id'=>$tmp_expire_bm['bm_id']), 'warn_day,quit_day');
                                //数组格式化批次货品的具体每个批次
                                $all_expire_bm_arr[] = array_merge($tmp_expire_bm,$basicMInfo,$basicMaterialConfInfo,array('branch_id'=>$branch_id,'bill_id'=>$iso_id,'bill_bn'=>$Iso['iso_bn'],'bill_type'=>$Iso['type_id'],'bill_io_type'=>1));
                                $all_expire_ids[] = $basicMInfo['bm_id'];
                                $all_expire_bn_ids[$basicMInfo['bm_id']] = $basicMInfo['material_bn'];
                                //重新计算批次货品的入库数量总数
                                if(isset($entry_num[$tmp_expire_bm['iso_items_id']])){
                                    $entry_num[$tmp_expire_bm['iso_items_id']] += $tmp_expire_bm['in_num'];
                                }else{
                                    $entry_num[$tmp_expire_bm['iso_items_id']] = $tmp_expire_bm['in_num'];
                                }
                            }
                        }
                    }
                }
                
                foreach($iso_items as $k => $iso_item){
                    if(!isset($entry_num[$iso_item['iso_items_id']]) || $iso_item['nums'] != $entry_num[$iso_item['iso_items_id']]){
                        $this->end(false, '物料：'.$iso_item['product_name'].'的调拨入库数量与要求入库数量不符', $back_url);
                    }
                }
                
                if(empty($all_expire_ids))
                {
                    $this->end(false, '保质期信息没有录入', $back_url);
                }
                
                foreach((array)$expire_bm_ids as $bm_id){
                    if(!in_array($bm_id,$all_expire_ids)){
                        $this->end(false, '物料：'.$all_expire_bn_ids[$bm_id].'的保质期信息没有录入', $back_url);
                    }
                }
            }
        }else{
            $label = '出库';
            
            //有保质期物料数据处理
            $all_expire_bm_arr = array();
            $has_expire_bn = false;
            if($expire_bm_ids)
            {
                $has_expire_bn = true;
                if($expire_bm_arr){
                    foreach($expire_bm_arr as $expire_bm){
                        $tmp_expire_bm_arr = array();
                        $tmp_expire_bm_arr = json_decode($expire_bm,true);
                        if($tmp_expire_bm_arr){
                            foreach($tmp_expire_bm_arr as $k => $tmp_expire_bm)
                            {
                                $basicMInfo = $basicMaterialObj->dump(array('bm_id'=>$tmp_expire_bm['bm_id']), 'bm_id, material_name, material_bn,material_bn_crc32');
                                $all_expire_ids[] = $basicMInfo['bm_id'];
                                $all_expire_bn_ids[$basicMInfo['bm_id']] = $basicMInfo['material_bn'];
                                
                                #查询保质期条码是否有效
                                $storageLifeInfo    = $basicMStorageLifeLib->checkStorageListBatchExist($branch_id, $tmp_expire_bm['bm_id'], $tmp_expire_bm['expire_bn'], $msg);
                                if($storageLifeInfo == false)
                                {
                                    $this->end(false, $msg, $back_url);
                                }
                                
                                if($storageLifeInfo['balance_num'] < $tmp_expire_bm['out_num'])
                                {
                                    $this->end(false, '保质期条码：'. $tmp_expire_bm['expire_bn'] .'输入的出库数量大于有效数量', $back_url);
                                }
                                
                                #采购入库流水单据信息
                                $all_expire_bm_arr[] = array(
                                        'branch_id' => $storageLifeInfo['branch_id'],
                                        'bm_id' => $storageLifeInfo['bm_id'],
                                        'expire_bn' => $storageLifeInfo['expire_bn'],
                                        'difference_num' => $tmp_expire_bm['out_num'],
                                        'bill_id' => $iso_id,
                                        'bill_bn' => $Iso['iso_bn'],
                                        'bill_type' => $Iso['type_id'],
                                        'bill_io_type' => $Iso['type_id'],
                                );
                                
                                #重新计算批次货品的入库数量总数
                                if(isset($entry_num[$tmp_expire_bm['iso_items_id']])){
                                    $entry_num[$tmp_expire_bm['iso_items_id']] += $tmp_expire_bm['out_num'];
                                }else{
                                    $entry_num[$tmp_expire_bm['iso_items_id']] = $tmp_expire_bm['out_num'];
                                }
                            }
                        }
                    }
                }
                
                foreach($iso_items as $k => $iso_item){
                    if(!isset($entry_num[$iso_item['iso_items_id']]) || $iso_item['nums'] != $entry_num[$iso_item['iso_items_id']]){
                        $this->end(false, '物料：'.$iso_item['product_name'].'的调拨出库数量与要求出库数量不符', $back_url);
                    }
                }
                
                if(empty($all_expire_ids))
                {
                    $this->end(false, '没有关联保质期条码', $back_url);
                }
                
                foreach((array)$expire_bm_ids as $bm_id){
                    if(!in_array($bm_id,$all_expire_ids)){
                        $this->end(false, '物料：'.$all_expire_bn_ids[$bm_id].'没有关联保质期条码', $back_url);
                    }
                }
            }
        }

        if($Iso['confirm']=='Y'){
            $this->end(false, '此单据已确认!', $back_url);
        }
        if (empty($ids)){
            $this->end(false, '请选择需要'.$label.'的商品', $back_url);
        }
        
        $ret = array();
        $error_bn = array();
        $oBranchProduct = app::get('ome')->model('branch_product');
        foreach ($ids as $k=>$id) {
            if ($entry_num[$id] <= 0){
                $this->end(false, ''.$label.'量必须大于0', $back_url);
            }

            if($io == '0'){
                $aRow = $oBranchProduct->dump(array('product_id'=>$_POST['product_ids'][$id], 'branch_id'=>$_POST['branch_id']),'store,store_freeze');
                if($entry_num[$id] > ($aRow['store'])){
                    $this->end(false, '出库数量不可大于库存数量.');
                }
            }
        }
         $iso_items = $oIsoItems->getList('*',array('iso_id'=>$iso_id));
         foreach($iso_items as $ik=>$iv){
             if(app::get('taoguaninventory')->is_installed()){
                 $check_inventory = kernel::single('taoguaninventory_inventorylist')->checkproductoper($iv['product_id'],$branch_id);

                if(!$check_inventory){
                    $this->end(false, '此商品正在盘点中，不可以出入库操作!', $back_url);
                }
            }
        }

        $err_msg = [];
         //保质期信息保存
         if($has_expire_bn)
         {
             if($io == '0')
             {
                 $is_save = $basicMReceiptStorageLifeLib->update($all_expire_bm_arr, $err_msg);
             }
             else 
             {
                 $is_save = $basicMReceiptStorageLifeLib->generate($all_expire_bm_arr, $err_msg);
             }
         }
         
         if(($has_expire_bn && $is_save) || !$has_expire_bn)
         {
             //事件触发，通知oms出入库
             $type_id = $_POST['type_id'];
             if($io){#入
                 kernel::single('wms_event_trigger_otherinstorage')->inStorage(array('iso_id'=>$iso_id, 'items'=>array()), true);
             }else{
                 kernel::single('wms_event_trigger_otheroutstorage')->outStorage(array('iso_id'=>$iso_id), true);
             }
             
             $this->end(true, $label.'完成');
         }else{
             $error_msg = is_array($err_msg) ? implode('!',$err_msg) : '批次信息保存失败';
             $this->end(false, $error_msg, $back_url);
         }
    }

  /**
   * 
   * 出入库单查询
   */
  function search_iostockorder(){
        $io = $_GET['io'];
        switch ($io){
            case '1':
                $this->base_filter = array();
                $this->title = '入库单查询';
                $confirm_label = '入库单确认';
                break;
            case '0':
                $this->base_filter = array();
                $this->title = '出库单查询';
                $confirm_label = '出库单确认';
                break;
        }

        $this->base_filter['confirm'] = 'Y';
        if($_POST['type_id']) {
            $this->base_filter['type_id'] = intval($_POST['type_id']);
        }else{
            $this->base_filter['type_id'] = kernel::single('taoguaniostockorder_iostockorder')->get_iso_type($io,true);
        }

        $this->finder('taoguaniostockorder_mdl_iso',array(
           'title' => $this->title,
           'actions' => array(
              // array('label'=>$confirm_label,'submit'=>'index.php?app=ome&ctl=admin_order&act=dispatching','target'=>'dialog::{width:400,height:200,title:\'订单分派\'}'),
           ),
           'base_filter' => $this->base_filter,
           'use_buildin_new_dialog' => false,
           'use_buildin_set_tag'=>false,
           'use_buildin_recycle'=>false,
           'use_buildin_export'=>true,
           'use_buildin_import'=>false,
           'use_buildin_filter'=>true,
           'finder_cols'=>'name,iso_bn,oper,operator,original_bn,create_time,type_id',
           //'finder_aliasname'=>$finder_aliasname,
           //'finder_cols'=>$finder_cols,
        ));
    }
    
    /*------------------------------------------------------ */
    //-- [调拨入库]编辑基础物料保质期条码
    /*------------------------------------------------------ */
    function storage_life_instock()
    {
        $iso_id = $_POST['iso_id'];
        $bm_id = $_POST['bm_id'];
        $has_expire_bm_info = $_POST['has_expire_bm_info'] ? $_POST['has_expire_bm_info'] : 1;
        if(empty($iso_id) || empty($bm_id))
        {
            die('无效操作，请检查！');
        }
        
        $oIso = app::get('taoguaniostockorder')->model("iso");
        $oIsoItems = app::get('taoguaniostockorder')->model("iso_items");
        $basicMaterialObj     = app::get('material')->model('basic_material');#基础物料
        $basicMStorageLifeLib    = kernel::single('material_storagelife');
        $basicMaterialStorageLifeObj = app::get('material')->model('basic_material_storage_life');
        
        #出入库单信息
        $iso = $oIso->dump($iso_id,'branch_id,supplier_id,type_id');
        
        #出入库单信息明细
        $iso_item    = $oIsoItems->dump(array('iso_id'=>$iso_id, 'product_id'=>$bm_id), 'iso_items_id, iso_id, bn, nums');
        if(empty($iso_item))
        {
            die('没有找到对应的出入库单信息');
        }
        
        #保质期配置信息
        $material_conf    = $basicMStorageLifeLib->getStorageLifeInfoById($bm_id);
        if(!$material_conf)
        {
            die('没有找到对应的基础物料保质期配置信息！');
        }
        
        $row    = $basicMaterialObj->dump(array('bm_id'=>$bm_id), 'bm_id, material_name, material_bn');
        if(empty($row))
        {
            die('没有找到对应的基础物料');
        }
        
        #已有保质期批次号[排除已过期的]
        $filter            = array('branch_id'=>$iso['branch_id'], 'bm_id'=>$bm_id, 'expiring_date|than'=>time());
        $storageLifeInfo   = $basicMaterialStorageLifeObj->getList('bmsl_id', $filter);
        $this->pagedata['exist_expire']        = ($storageLifeInfo ? 'true' : 'false');#标记
        
        $this->pagedata['has_expire_bm_info']          = $has_expire_bm_info;
        $this->pagedata['iso_item']          = $iso_item;
        $this->pagedata['time_from']         = date('Y-m-d', time());
        $this->pagedata['item']              = $row;
        $this->pagedata['material_conf']     = $material_conf;
        $this->page('admin/iostock/storage_life_instock.html');
    }
    
    /*------------------------------------------------------ */
    //-- [关联保质期]JSON组织数据
    /*------------------------------------------------------ */
    function do_storage_life_instock()
    {
        $iso_id   = $_POST['iso_id'];
        $bm_id    = $_POST['bm_id'];
        $iso_items_id    = $_POST['iso_items_id'];
        
        $expire_barcode   = $_POST['expire_barcode'];
        $expire_num       = $_POST['expire_num'];
        $production_date       = $_POST['production_date'];
        $date_type       = $_POST['date_type'];
        $guarantee_period       = $_POST['guarantee_period'];
        $expiring_date       = $_POST['expiring_date'];
        
        #检测重复的保质期条码
        $unique_arr    = array_unique($expire_barcode);
        $repeat_arr    = array_diff_assoc($expire_barcode, $unique_arr);
        if($repeat_arr)
        {
            die(json_encode(array('code' => 'FAIL', 'msg' => '存在多个重复的条码,code：'. implode(',', $repeat_arr))));
        }
        
        //生成保质期数据内容字符串
        $save_data = array();
        $count = 0;
        foreach ($expire_barcode as $key => $val)
        {
            $save_data[$key]['iso_items_id']  = $iso_items_id;
            $save_data[$key]['bm_id']         = $bm_id;
            $save_data[$key]['expire_bn']     = $val;#物料保质期编码
            $save_data[$key]['in_num']        = $expire_num[$key];#入库数量
            
            $save_data[$key]['production_date']    = $production_date[$key];#生产日期
            $save_data[$key]['date_type']          = $date_type[$key];
            $save_data[$key]['guarantee_period']   = $guarantee_period[$key];#保质期
            $save_data[$key]['expiring_date']      = $date_type[$key] == 'date' ? $expiring_date[$key] : '';#过期日期
            $count +=$save_data[$key]['in_num'];
        }
        
        $msg = json_encode($save_data);
        echo json_encode(array('code' => 'SUCC', 'msg' => $msg, 'count'=>$count));
    }
    
    /*------------------------------------------------------ */
    //-- [调拨出库]绑定基础物料保质期条码
    /*------------------------------------------------------ */
    function bind_storage_life()
    {
       
        $iso_id = $_POST['iso_id'];
        $bm_id = $_POST['bm_id'];
        $has_expire_bm_info = $_POST['has_expire_bm_info'] ? $_POST['has_expire_bm_info'] : 1;
        if(empty($iso_id) || empty($bm_id))
        {
            die('无效操作，请检查！');
        }
       
       
        $oIsoItems = app::get('taoguaniostockorder')->model("iso_items");
        $basicMaterialObj     = app::get('material')->model('basic_material');#基础物料
        $basicMStorageLifeLib    = kernel::single('material_storagelife');
        
        #出入库单信息明细
        $iso_item    = $oIsoItems->dump(array('iso_id'=>$iso_id, 'product_id'=>$bm_id), 'iso_items_id, iso_id, bn, nums');
        if(empty($iso_item))
        {
            die('没有找到对应的出入库单信息');
        }
        
        #基础物料保质期开关
        $material_conf    = $basicMStorageLifeLib->checkStorageLifeById($bm_id);
        if(!$material_conf)
        {
            die('基础物料保质期开关未开启！');
        }
        
        $row    = $basicMaterialObj->dump(array('bm_id'=>$bm_id), 'bm_id, material_name, material_bn');
        if(empty($row))
        {
            die('没有找到对应的基础物料');
        }
       
        $this->pagedata['has_expire_bm_info']  = $has_expire_bm_info;
        $this->pagedata['iso_item']            = $iso_item;
        $this->pagedata['time_from']           = date('Y-m-d', time());
        $this->pagedata['item']                = $row;
        $this->page('admin/iostock/bind_storage_life_outstock.html');
    }
    
    //根据保质期条码检查是否存在有效
    function checkExpireBn()
    {
        $iso_id = $_POST['iso_id'];
        $bm_id = $_POST['bm_id'];
        $expire_bn = $_POST['expire_bn'];
        $out_num = $_POST['out_num'];
        $msg = '';
        
        if(empty($expire_bn) || empty($bm_id) || empty($iso_id) || empty($out_num))
        {
            die('empty');
        }
        
        $codebaseLib= kernel::single('material_codebase');
        $res = $codebaseLib->checkBmHasThisStorageListBn($bm_id, $expire_bn);
        
        //检查保质期条码明细
        if($res)
        {
            $oIso = app::get('taoguaniostockorder')->model('iso');
            $iso_row = $oIso->dump(array('iso_id'=>$iso_id), 'branch_id');
            
            $basicMStorageLifeLib    = kernel::single('material_storagelife');
            $expire_bn_row           = $basicMStorageLifeLib->checkStorageListBatchExist($iso_row['branch_id'], $bm_id, $expire_bn, $msg);
            if($expire_bn_row == false)
            {
                die('fail');
            }
            
            if($expire_bn_row['balance_num'] < $out_num)
            {
                die('error_num');
            }
        }
        
        echo $res;
    }
    
    #绑定关联保质期条码明细
    function do_bind_storage_life()
    {
        $iso_id           = $_POST['iso_id'];
        $bm_id            = $_POST['bm_id'];
        $iso_items_id     = $_POST['iso_items_id'];
        
        $expire_barcode   = $_POST['expire_barcode'];
        $expire_num       = $_POST['expire_num'];
        
        #检测重复的保质期条码
        $unique_arr    = array_unique($expire_barcode);
        $repeat_arr    = array_diff_assoc($expire_barcode, $unique_arr);
        if($repeat_arr)
        {
            die(json_encode(array('code' => 'FAIL', 'msg' => '存在多个重复的条码,code：'. implode(',', $repeat_arr))));
        }
        
        //生成保质期数据内容字符串
        $save_data = array();
        $count = 0;
        foreach ($expire_barcode as $key => $val)
        {
            $save_data[$key]['iso_items_id']  = $iso_items_id;
            $save_data[$key]['bm_id']         = $bm_id;
            
            $save_data[$key]['expire_bn']     = $val;
            $save_data[$key]['out_num']       = $expire_num[$key];
            $count +=$save_data[$key]['out_num'];
            
        }
        
        $msg = json_encode($save_data);
        echo json_encode(array('code' => 'SUCC', 'msg' => $msg, 'count'=>$count));
    }
    
    /*
     * 检查保质期物料是否存在
     */
    function isExistExpireBn()
    {
        $iso_id   = $_POST['iso_id'];
        $bm_id    = $_POST['bm_id'];
        $expire_bn        = $_POST['expire_bn'];
        $date_type_list   = array(1=>'day', 'month', 'year', 'date');
        
        if(empty($iso_id) || empty($bm_id) || empty($expire_bn))
        {
            echo json_encode(array('code' => 'error', 'msg' => '无效操作'));
            exit;
        }
        
        $oIso    = app::get('taoguaniostockorder')->model('iso');
        $basicMaterialStorageLifeObj    = app::get('material')->model('basic_material_storage_life');
        
        #采购信息
        $iso_row    = $oIso->dump(array('iso_id'=>$iso_id), 'branch_id');
        
        #保质期批次号
        $filter    = array('branch_id'=>$iso_row['branch_id'], 'bm_id'=>$bm_id, 'expire_bn'=>$expire_bn);
        $row       = $basicMaterialStorageLifeObj->dump($filter, 'bmsl_id, guarantee_period, production_date, expiring_date, date_type');
        if(empty($row))
        {
            echo json_encode(array('code' => 'error', 'msg' => '没有相关保质期批次号'));
            exit;
        }
        elseif($row['expiring_date'] < time())
        {
            echo json_encode(array('code' => 'error', 'msg' => '保质期批次号已经过期'));
            exit;
        }
        
        $data    = array('code' => 'SUCC', 'production_date'=>date('Y-m-d', $row['production_date']));
        $data['date_type']           = $date_type_list[$row['date_type']];
        $data['guarantee_period']    = $row['guarantee_period'];
        $data['expire_bn']           = $row['expire_bn'];
        
        echo json_encode($data);
        exit;
    }
}
?>
