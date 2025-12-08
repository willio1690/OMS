<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class wms_ctl_admin_returned_purchase extends desktop_controller{

    var $name = "退货单";
    var $workground = "wms_center";

    function oList() {
        $params = array(
            'title'=> '采购退货',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => false,
            'use_buildin_export' => false,
            'use_buildin_import' => false,
            'use_buildin_filter' => true,
            'orderBy' => 'returned_time desc',
            'base_filter' => array('rp_type'=>'eo','return_status'=>'1','check_status'=>'2'),
        	'finder_cols'=>'column_edit,supplier_id,name,product_cost,delivery_cost,amount,logi_no,return_status,operator',
        );

        //仓库过滤的条件定义放下面，不然定义被会冲掉失效
        $is_super = kernel::single('desktop_user')->is_super();
        $branch_ids = kernel::single('wms_branch')->getBranchwmsByUser($is_super);
        if ($branch_ids){
            $params['base_filter']['branch_id'] = $branch_ids;
        }else{
            $params['base_filter']['branch_id'] = 'false';
        }

        $this->finder('purchase_mdl_returned_purchase', $params);
    }


    /**
     * 退货出库
     *
     */
    function purchaseShift($rp_id){
        
        $this->begin('index.php?app=wms&ctl=admin_returned_purchase&act=oList');
        if (empty($rp_id)){
            $this->end(false,'操作出错，请重新操作');
        }
        $rpObj = app::get('purchase')->model('returned_purchase');
        $suObj = app::get('purchase')->model('supplier');
        $brObj = app::get('ome')->model('branch');
        
        $data = $rpObj->dump($rp_id, '*', array('returned_purchase_items' => array('*')));
        //当前供应商
        $supplier_detail = $suObj->dump($data['supplier_id'], 'supplier_id,name');
        $this->pagedata['supplier_detail'] = $supplier_detail;

        /*编辑不允许改变仓库，所以默认为单仓库
        //获取仓库模式
        $branch_mode = app::get('ome')->getConf('ome.branch.mode');
        */
        
        #退货单明细
        $basicMaterialObj        = app::get('material')->model('basic_material');#基础物料
        $basicMaterialBarcode    = kernel::single('material_basic_material_barcode');
        $basicMStorageLifeLib    = kernel::single('material_storagelife');
        
        $piObj = app::get('purchase')->model('returned_purchase_items');
        $purchase_items = $piObj->getList('item_id,product_id,num,price,bn,name,spec_info,barcode',array('rp_id'=>$rp_id),0,-1);
        
        $material_ext_row    = array();
        foreach ($purchase_items as $key => $value)
        {
            $purchase_items[$key]['entry_num'] = $value['num'];
            
            #基础物料是否可售
            $material_ext_row    = $basicMaterialObj->dump(array('bm_id'=>$value['product_id']), 'bm_id, visibled');
            $purchase_items[$key]['visibility'] = ($material_ext_row['visibility'] ? 'true' : 'false');
            
            #条形码
            $barcode_val    = $basicMaterialBarcode->getBarcodeById($value['product_id']);
            $purchase_items[$key]['barcode'] = $barcode_val;
            
            # [开启]保质期监控
            $get_material_conf    = $basicMStorageLifeLib->checkStorageLifeById($value['product_id']);
            if($get_material_conf)
            {
                $purchase_items[$key]['use_expire'] = 1;
            }
        }
        $this->pagedata['purchase_items'] = $purchase_items;
        
        if (!$branch_mode){
            $branch_mode = 'single';
        }
        $this->pagedata['branch_mode'] = $branch_mode;

        $su = $suObj->dump($data['supplier_id'],'name');
        $br = $brObj->dump($data['branch_id'], 'name');
        $data['branch_name']   = $br['name'];
        $data['supplier_name'] = $su['name'];
        $this->pagedata['po_items'] = $data['returned_purchase_items'];
        $data['memo'] = unserialize($data['memo']);
        $this->pagedata['po'] = $data;
        $this->singlepage("admin/returned/purchase/purchase_shift.html");
    }

     /**
     * 保存退货出库
     *
     */
    function doShift()
    {
        $basicMaterialLib    = kernel::single('material_basic_material');
        
        $this->begin('index.php?app=wms&ctl=admin_returned_purchase&act=oList');
        $rp_id = $_POST['rp_id'];
        //$at = $_POST['at'];
        $pr = $_POST['pr'];
        $ato = $_POST['at_o'];
        $ids = $_POST['ids'];
        
        $entry_num = $_POST['entry_num'];
        $branch_id = $_POST['branch_id'];

        $rpObj = app::get('purchase')->model('returned_purchase');
        $rp_itemObj = app::get('purchase')->model('returned_purchase_items');
        
        $data = $rpObj->dump($rp_id, '*', array('returned_purchase_items'=>array('*')));

        $total = 0;
        if(empty($ids) || empty($pr)){
            $this->end(false, '暂无出库货品', 'index.php?app=wms&ctl=admin_returned_purchase&act=purchaseShift');
        }
        /*
        foreach($at as $k=>$v){
            if($v != $ato[$k]){
               $this->end(false, '出库数量与退货数量不符', 'index.php?app=wms&ctl=admin_returned_purchase&act=purchaseShift');
            }

        }
        */
       
        #关联保质期物料
        $basicMaterialObj     = app::get('material')->model('basic_material');
        $basicMaterialConf    = app::get('material')->model('basic_material_conf');
        $basicMReceiptStorageLifeLib = kernel::single('material_receipt_storagelife');
        $basicMStorageLifeLib    = kernel::single('material_storagelife');
        
        $expire_bm_ids = $_POST['is_expire_bn'];
        $expire_bm_arr = $_POST['expire_bm_info'];
        $back_url    = 'index.php?app=wms&ctl=admin_returned_purchase&act=purchaseShift';
        
        $rp_type_id    = 10;#采购退货类型
        
        #有保质期物料数据处理
        $all_expire_bm_arr = array();
        $has_expire_bn = false;
        if($expire_bm_ids)
        {
            $has_expire_bn = true;
            if($expire_bm_arr)
            {
                foreach($expire_bm_arr as $expire_bm)
                {
                    $tmp_expire_bm_arr = array();
                    $tmp_expire_bm_arr = json_decode($expire_bm,true);
                    
                    if($tmp_expire_bm_arr)
                    {
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
                                    'bill_id' => $rp_id,
                                    'bill_bn' => $data['rp_bn'],
                                    'bill_type' => $rp_type_id,
                                    'bill_io_type' => $rp_type_id,
                            );
                            
                            #重新计算批次货品的入库数量总数
                            if(isset($entry_num[$tmp_expire_bm['rp_item_id']]))
                            {
                                $entry_num[$tmp_expire_bm['rp_item_id']] += $tmp_expire_bm['out_num'];
                            }else{
                                $entry_num[$tmp_expire_bm['rp_item_id']] = $tmp_expire_bm['out_num'];
                            }
                        }
                    }
                }
            }
            
            foreach($data['returned_purchase_items'] as $k => $rp_item){
                if(!isset($entry_num[$rp_item['item_id']]) || $rp_item['num'] != $entry_num[$rp_item['item_id']]){
                    $this->end(false, '物料：'.$rp_item['name'].'的调拨出库数量与要求出库数量不符', $back_url);
                }
            }
            
            foreach((array)$expire_bm_ids as $bm_id){
                if(!in_array($bm_id,$all_expire_ids)){
                    $this->end(false, '物料：'.$all_expire_bn_ids[$bm_id].'没有关联保质期条码', $back_url);
                }
            }
        }
        else 
        {
            #无保质期物料数据处理
            foreach($data['returned_purchase_items'] as $k => $rp_item)
            {
                if(!isset($entry_num[$rp_item['item_id']]) || ($rp_item['num'] != $entry_num[$rp_item['item_id']]))
                {
                    $this->end(false, '物料 '.$rp_item['name'].' 的调拨出库数量与要求出库数量不符', $back_url);
                }
            }
        }
        
        foreach($ids as $k=> $i){
            $rp_items = $rp_itemObj->dump($i,'price,product_id,num,barcode,name,spec_info,bn');

            $Products     = $basicMaterialLib->getBasicMaterialDetail($rp_items['product_id']);
            
             if(app::get('taoguaninventory')->is_installed()){
                 $check_inventory = kernel::single('taoguaninventory_inventorylist')->checkproductoper($rp_items['product_id'],$data['branch_id']);

                if(!$check_inventory){
                    $this->end(false, '此商品正在盘点中，不可以出入库操作!', 'index.php?app=wms&ctl=admin_returned_purchase&act=purchaseShift');
                }
             }
            $total += $entry_num[$k]*$rp_items['price'];
            $shift_items[$rp_items['product_id']] = array(
                'product_id' => $rp_items['product_id'],
                'product_bn' => $rp_items['bn'],
                'name' => $rp_items['name'],
                'spec_info' => $rp_items['spec_info'],
                'bn' => $rp_items['bn'],
                'unit' => $Products['unit'],
                'store' => $Products['store'],
                'price' => $rp_items['price'],//1212增加
                'nums' => $entry_num[$i],
              );
        }

        foreach($shift_items as $v){
            if($v['nums'] > $v['store']){
               $this->end(false, '产品条码: ' . $v['product_bn'].' 出库数量大于实际库存', 'index.php?app=wms&ctl=admin_returned_purchase&act=purchaseShift');
            }
        }
        
        //保质期信息保存
        if($has_expire_bn)
        {
            $msg = [];
            $is_save = $basicMReceiptStorageLifeLib->update($all_expire_bm_arr, $msg);
        }
        
        if(($has_expire_bn && $is_save) || !$has_expire_bn)
        {
            //事件触发，通知oms采购退货单入库
            $outdata = array(
                    'rp_id'=>$rp_id,
                    'memo'=>htmlspecialchars($_POST['memo']),
                    'items'=>$shift_items,
                    'expire_bm_arr'=>$expire_bm_arr,
            );
            
            kernel::single('wms_event_trigger_purchasereturn')->outStorage($outdata, true);
            
            $this->end(true, '出库成功');
        }else{
            $error_msg = is_array($msg) ? implode('!',$msg) : '批次信息保存失败';
            $this->end(false, $error_msg, $back_url);
        }
    }

    /**
     * 打印退货单
     *
     * @param int $rp_id
     */
    function printItem($rp_id){
        $rpObj = app::get('purchase')->model('returned_purchase');
        $suObj = app::get('purchase')->model('supplier');
        $brObj = app::get('ome')->model('branch');
        
        $rp = $rpObj->dump($rp_id, '*', array('returned_purchase_items'=>array('*')));
        $su = $suObj->dump($rp['supplier_id'],'name');
        $bran = $brObj->dump($rp['branch_id'],'name');
        $rp['supplier'] = $su['name'];
        $rp['branch'] = $bran['name'];
        $rp['memo'] = unserialize($rp['memo']);
        $rp['po_items'] = $rp['returned_purchase_items'];
        $this->pagedata['po'] = $rp;
        $this->pagedata['time'] = time();
        $this->pagedata['base_dir'] = kernel::base_url();

        kernel::single('ome_print_otmpl')->printOTmpl($_GET['otmplId'],'purreturn',$this);
 
    }

    /**
     * 拒绝退货
     *
     * @param int $po_id
     */
    function cancel($rp_id){
        $rpObj = app::get('purchase')->model('returned_purchase');
        if(count($_POST)>0){
            $rp_id = $_POST['rp_id'];
            $rp = $rpObj->dump($rp_id, 'memo,rp_bn,branch_id');
            $operator = $_POST['operator'];
            $this->begin('index.php?app=wms&ctl=admin_returned_purchase&act=oList');
            if (empty($rp_id)){
                $this->end(false,'操作出错，请重新操作');
            }
            $newmemo =  htmlspecialchars($_POST['memo']);
            $data = array('io_bn'=>$rp['rp_bn'],'io_type'=>'PURCHASE_RETURN','branch_id'=>$rp['branch_id'],'memo'=>$newmemo);
            kernel::single('wms_event_trigger_purchasereturn')->cancel($data, true);
            #kernel::single('wms_iostockdata')->notify_purchaseReturn(array('rp_id'=>$rp_id,'memo'=>$newmemo),'CANCEL');
            $this->end(true, '出库拒绝已完成');
        }else{
            $rp = $rpObj->dump($rp_id, 'supplier_id');
            $oSupplier = app::get('purchase')->model('supplier');
            $supplier = $oSupplier->dump($rp['supplier_id'], 'operator');
            $this->pagedata['operator'] = kernel::single('desktop_user')->get_name();
            $this->pagedata['id'] = $rp_id;
            $this->display("admin/returned/purchase/purchase_cancel.html");
        }
    }
    #使用扫描枪时，根据条形码,获取product_id
    function getProductId()
    {
        $basicMaterialObj    = app::get('material')->model('basic_material');
        $basicMaterialBarcode    = kernel::single('material_basic_material_barcode');
        
        $barcode = $_POST['barcode'];
        
        #查询条形码对应的bm_id
        $bm_ids    = $basicMaterialBarcode->getBmidListByBarcode($barcode);
        
        $product_id    = $basicMaterialObj->dump(array('bm_id'=>$bm_ids), 'bm_id');
        
        if(!empty($product_id['bm_id']))
        {
            echo $product_id['bm_id'];
        }else{
            echo NULL;
        }
    }

    /*------------------------------------------------------ */
    //-- [采购退货]绑定基础物料保质期条码
    /*------------------------------------------------------ */
    function bind_storage_life_purchase()
    {
        $rp_id = $_POST['rp_id'];
        $bm_id = $_POST['bm_id'];
        $has_expire_bm_info = $_POST['has_expire_bm_info'] ? $_POST['has_expire_bm_info'] : 1;
        if(empty($rp_id) || empty($bm_id))
        {
            die('无效操作，请检查！');
        }
        
        $piObj = app::get('purchase')->model('returned_purchase_items');
        $basicMaterialObj     = app::get('material')->model('basic_material');#基础物料
        $basicMStorageLifeLib    = kernel::single('material_storagelife');
    
        #出入库单信息明细
        $rp_item    = $piObj->dump(array('rp_id'=>$rp_id, 'product_id'=>$bm_id), 'item_id, rp_id, bn, num');
        if(empty($rp_item))
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
        $this->pagedata['rp_item']             = $rp_item;
        $this->pagedata['time_from']           = date('Y-m-d', time());
        $this->pagedata['item']                = $row;
        $this->page('admin/returned/purchase/bind_storage_life_purchase.html');
    }
    
    #根据保质期条码检查是否存在有效
    function checkExpireBn()
    {
        $rp_id = $_POST['rp_id'];
        $bm_id = $_POST['bm_id'];
        $expire_bn = $_POST['expire_bn'];
        $out_num = $_POST['out_num'];
        
        if(empty($expire_bn) || empty($bm_id) || empty($rp_id) || empty($out_num))
        {
            die('empty');
        }
        
        $codebaseLib= kernel::single('material_codebase');
        $res = $codebaseLib->checkBmHasThisStorageListBn($bm_id, $expire_bn);
    
        #检查保质期条码明细
        if($res)
        {
            $rpObj = app::get('purchase')->model('returned_purchase');
            $rp_row = $rpObj->dump(array('rp_id'=>$rp_id), 'branch_id');
            
            $basicMStorageLifeLib    = kernel::single('material_storagelife');
            $expire_bn_row           = $basicMStorageLifeLib->checkStorageListBatchExist($rp_row['branch_id'], $bm_id, $expire_bn, $msg);
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
    function do_bind_storage_life_purchase()
    {
        $rp_id            = $_POST['rp_id'];
        $bm_id            = $_POST['bm_id'];
        $rp_item_id       = $_POST['rp_item_id'];
        
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
            $save_data[$key]['rp_item_id']    = $rp_item_id;
            $save_data[$key]['bm_id']         = $bm_id;
        
            $save_data[$key]['expire_bn']     = $val;
            $save_data[$key]['out_num']       = $expire_num[$key];
            $count +=$save_data[$key]['out_num'];
        
        }
        
        $msg = json_encode($save_data);
        echo json_encode(array('code' => 'SUCC', 'msg' => $msg, 'count'=>$count));
    }
}