<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_ctl_admin_eo extends desktop_controller{
    var $name = "入库管理";
    var $workground = "wms_center";

    function eo_confirm($po_id)
    {
        $basicMaterialExtObj = app::get('material')->model('basic_material_ext');
        $libBranchProductPos    = kernel::single('ome_branch_product_pos');
        $basicMStorageLifeLib    = kernel::single('material_storagelife');
        
        $oPo = app::get('purchase')->model("po");
        $oPo_items = app::get('purchase')->model("po_items");
        
        $perpage = 200;
        $page = intval($_GET['page'])?intval($_GET['page']):1;
        $start = ($page-1)*$perpage;

        if($page>=200){
            $top_start=$start+1;
        }else{
            $top_start=$page;
        }
        $top_title = $top_start.'-'.$page*$perpage;
        $base_url = 'index.php?app=wms&ctl=admin_eo&act=eo_confirm_list&p[0]='.$po_id;
        $count = count($oPo_items->getList('*',array('po_id'=>$po_id), 0, -1));
        $multi = $this->multipages($count,$perpage,$page,$base_url);
        $Po_items = $oPo_items->getList('*',array('po_id'=>$po_id));
        $Po = $oPo->dump($po_id,'branch_id,supplier_id');
        foreach($Po_items as $k=>$v)
        {
            $product    = $basicMaterialExtObj->dump(array('bm_id'=>$v['product_id']), 'bm_id, unit');
            
            $Po_items[$k]['unit'] = $product['unit'];
            
            $assign = $libBranchProductPos->get_pos($v['product_id'],$Po['branch_id']);
            if(empty($assign)){
                $pos_list = $libBranchProductPos->get_unassign_pos($Po['branch_id']);
                $Po_items[$k]['is_new']="true";
            }else{
                $Po_items[$k]['is_new']="false";
                $pos_list = $assign;
            }

            //查询物料是否保质期物料
            $is_use_expire    = $basicMStorageLifeLib->checkStorageLifeById($v['product_id']);
            $Po_items[$k]['use_expire'] = $is_use_expire ? 1 : 0;

            //根据采购单获取已采购入库的批次信息
            $storageLifeBatch = $basicMStorageLifeLib->getStorageLifeBillById($Po['branch_id'], $po_id, 1, $v['product_id']);
            $Po_items[$k]['instock_storagelife'] = $storageLifeBatch ? json_encode($storageLifeBatch) : '';

            $Po_items[$k]['spec_info'] = $v['spec_info'];
            $Po_items[$k]['entry_num'] = $v['num']-$v['in_num'];
            $Po_items[$k]['pos_list']=$pos_list;
        }

        //获取采购单供应商经办人/负责人
        $oSupplier = app::get('purchase')->model('supplier');
        $supplier = $oSupplier->dump($Po['supplier_id'], 'operator');
        if (!$supplier['operator']) $supplier['operator'] = '未知';
        $this->pagedata['operator'] = kernel::single('desktop_user')->get_name();
        $this->pagedata['Po_items'] = $Po_items;
        $this->pagedata['po_id'] = $po_id;
        $this->pagedata['multi']=$multi;
        $this->pagedata['count']=$count;//branch_id
        $this->pagedata['branch_id']=$Po['branch_id'];
        $this->pagedata['top_title'] = $top_title;
        $this->singlepage("admin/eo/eo_confirm.html");
    }

    function eo_confirm_list()
    {
        $basicMaterialObj = app::get('material')->model('basic_material');
        $libBranchProductPos    = kernel::single('ome_branch_product_pos');
        
        $po_id = $_GET['po_id'];
        $oPo = app::get('purchase')->model("po");
        $oPo_items = app::get('purchase')->model("po_items");
        
        $perpage = 200;
        $page = intval($_GET['page'])?intval($_GET['page']):1;
        $start = ($page-1)*$perpage;

        if($page>=200){
            $top_start=$start+1;
        }else{
            $top_start=$page;
        }
        $top_title = $top_start.'-'.$page*$perpage;
        $base_url = 'index.php?app=wms&ctl=admin_eo&act=eo_confirm_list&p[0]='.$po_id;
        $count = count($oPo_items->getList('*',array('po_id'=>$po_id), 0, -1));
        $multi = $this->multipages($count,$perpage,$page,$base_url);
        $Po_items = $oPo_items->getList('*',array('po_id'=>$po_id), $start, $perpage);
        $Po = $oPo->dump($po_id,'*');
        foreach($Po_items as $k=>$v)
        {
            $product    = $basicMaterialObj->dump(array('bm_id'=>$v['product_id']), '*');
            
            if(empty($product)){
                $Po_items[$k]['name'] = '此商品已不存在';
            }
          
            $assign = $libBranchProductPos->get_pos($v['product_id'],$Po['branch_id']);

            if(empty($assign))
            {
                $pos_list = $libBranchProductPos->get_unassign_pos($Po['branch_id']);
                
                $Po_items[$k]['is_new']="true";
            }else{
                $Po_items[$k]['is_new']="false";
                $pos_list = $assign;
            }
            $Po_items[$k]['entry_num'] = $v['num']-$v['in_num'];
            $Po_items[$k]['pos_list']=$pos_list;
           

        }

        //获取采购单供应商经办人/负责人
        $oSupplier = app::get('purchase')->model('supplier');
        $supplier = $oSupplier->dump($Po['supplier_id'], 'operator');
        if (!$supplier['operator']) $supplier['operator'] = '未知';
        $this->pagedata['operator'] = $supplier['operator'];
        $this->pagedata['Po_items'] = $Po_items;
        $this->pagedata['po_id'] = $po_id;
        $this->pagedata['multi']=$multi;
        $this->pagedata['count']=$count;//branch_id
        $this->pagedata['branch_id']=$Po['branch_id'];
        $this->pagedata['top_title'] = $top_title;

      $this->display("admin/eo/eo_confirmlist.html");
    }

    /**
     * 保存采购信息入库
     */
    function save_eo_confirm(){
        $this->begin('index.php?app=wms&ctl=admin_purchase&act=eoList&p[0]=i');
        $oPo_items = app::get('purchase')->model("po_items");
        $oEo = app::get('purchase')->model("eo");
        $basicMaterialObj     = app::get('material')->model('basic_material');
        $basicMaterialConf    = app::get('material')->model('basic_material_conf');
        $basicMReceiptStorageLifeLib = kernel::single('material_receipt_storagelife');
        $basicMStorageLifeLib    = kernel::single('material_storagelife');
        //$codeBaseLib = kernel::single('material_codebase');

        $entry_num = $_POST['entry_num'];
        $po_id = $_POST['po_id'];
        $ids = $_POST['ids'];//当前入库的明细item_id标记
        $branch_id = $_POST['branch_id'];
        $expire_bm_ids = $_POST['is_expire_bn'];
        $expire_bm_arr = $_POST['expire_bm_info'];
        if (isset($_POST['arrival_no']) && preg_match("/[\x7f-\xff]/", $_POST['arrival_no'])) {
            $this->end(false, '到货单号不能包含中文', 'index.php?app=wms&ctl=admin_eo&act=eo_confirm&p[0]='.$po_id);
        }
        //有保质期物料数据处理
        $all_expire_bm_arr = array();
        $has_expire_bn = false;
        if($expire_bm_ids){
            $has_expire_bn = true;
            $poObj = app::get('purchase')->model("po");
            $poBillInfo = $poObj->dump(array('po_id'=>$po_id),'po_bn');

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
                                    $this->end(false, '保质期条码已被关闭停用：'.$tmp_expire_bm['expire_bn'], 'index.php?app=wms&ctl=admin_eo&act=eo_confirm&p[0]='.$po_id);
                                }
                            }
                            
                            $basicMInfo = $basicMaterialObj->dump(array('bm_id'=>$tmp_expire_bm['bm_id']), 'bm_id, material_name, material_bn,material_bn_crc32');
                            $basicMaterialConfInfo = $basicMaterialConf->dump(array('bm_id'=>$tmp_expire_bm['bm_id']), 'warn_day,quit_day');
                            //数组格式化批次货品的具体每个批次
                            $all_expire_bm_arr[] = array_merge($tmp_expire_bm,$basicMInfo,$basicMaterialConfInfo,array('branch_id'=>$branch_id,'bill_id'=>$po_id,'bill_bn'=>$poBillInfo['po_bn'],'bill_type'=>1,'bill_io_type'=>1));
                            $all_expire_ids[] = $basicMInfo['bm_id'];
                            $all_expire_bn_ids[$basicMInfo['bm_id']] = $basicMInfo['material_bn'];
                            //重新计算批次货品的入库数量总数
                            if(isset($entry_num[$tmp_expire_bm['item_id']])){
                                $entry_num[$tmp_expire_bm['item_id']] += $tmp_expire_bm['in_num'];
                            }else{
                                $entry_num[$tmp_expire_bm['item_id']] = $tmp_expire_bm['in_num'];
                            }
                        }
                    }
                }
            }
            
            if($entry_num){
                foreach($entry_num as $k =>$val){
                    $_POST['entry_num'][$k] = $val;
                }
            }

            if(empty($all_expire_ids))
            {
                $this->end(false, '保质期信息没有录入', 'index.php?app=wms&ctl=admin_eo&act=eo_confirm&p[0]='.$po_id);
            }
            
            foreach((array)$expire_bm_ids as $bm_id){
                if(!in_array($bm_id,$all_expire_ids)){
                    $this->end(false, '物料：'.$all_expire_bn_ids[$bm_id].'的保质期信息没有录入', 'index.php?app=wms&ctl=admin_eo&act=eo_confirm&p[0]='.$po_id);
                }
            }
        }
        //error_log(var_export($entry_num,true),3,__FILE__.".2.log");
        if (empty($ids)){
            $this->end(false, '请选择需要入库的商品', 'index.php?app=wms&ctl=admin_eo&act=eo_confirm&p[0]='.$po_id);
        }
        $ret = array();
        foreach($ids as $i){
            if ($entry_num[$i] <= 0){
                $this->end(false, '入库量必须大于0', 'index.php?app=wms&ctl=admin_eo&act=eo_confirm&p[0]='.$po_id);
            }

            $Po_items=$oPo_items->dump(array('po_id'=>$po_id,'item_id'=>$i),'num,in_num,product_id');
            $p_entry_num = $Po_items['num']-$Po_items['in_num'];
            if($entry_num[$i]>$p_entry_num){
               $this->end(false, '入库量大于可入库量', 'index.php?app=wms&ctl=admin_eo&act=eo_confirm&p[0]='.$po_id);
            }

            if(app::get('taoguaninventory')->is_installed()){
                $check_inventory = kernel::single('taoguaninventory_inventorylist')->checkproductoper($Po_items['product_id'],$branch_id);
                if(!$check_inventory){
                    $this->end(false, '此商品正在盘点中，不可以入库!', 'index.php?app=wms&ctl=admin_eo&act=eo_confirm&p[0]='.$po_id);
                }
            }
        }
        $msg = [];
        //保质期信息保存
        $is_save = $basicMReceiptStorageLifeLib->generate($all_expire_bm_arr,$msg);
        //如果有批次信息
        if(($has_expire_bn && $is_save) || !$has_expire_bn){
            //更新采购入库单、更新库存数、生成出入库明细
            kernel::single('wms_eo')->save_eo($_POST);


           //事件触发，通知oms采购单入库 add by danny event notify
            kernel::single('wms_event_trigger_purchase')->inStorage($_POST,true);
            #$result = kernel::single('wms_eo')->notify_purchase($po_id,$_POST,'create');
            
            $this->end(true, '入库成功');
        }else{
            $error_msg = is_array($msg) ? implode('!',$msg) : '批次信息保存失败';
            $this->end(false, $error_msg, 'index.php?app=wms&ctl=admin_eo&act=eo_confirm&p[0]='.$po_id);
        }

    }

    /**
     * 条码控制入库
     */
    function Barcode_stock($po_id){
        $oPo = app::get('purchase')->model("po");
        $Po = $oPo->dump($po_id,'branch_id,supplier_id');
        $this->pagedata['branch_id']=$Po['branch_id'];
        $this->pagedata['po_id'] = $po_id;
        $stock_confirm= app::get('ome')->getConf('purchase.stock_confirm');
        $stock_cancel= app::get('ome')->getConf('purchase.stock_cancel');
        $this->pagedata['stock_confirm'] = $stock_confirm;
        $this->pagedata['stock_cancel'] = $stock_cancel;
        $this->singlepage("admin/eo/eo_barcode.html");
    }

    /**
     * 分页函数
     */
    function multipages($count,$perpage,$curr_page,$mpurl) {

        //if($count > $perpage) {
                $page = 200;
                $offset = 200;
                $pages = ceil($count / $perpage);
                //$multipage .= "<span class=nohref>".$curr_page." - ".$pages."</span>";
                $from = $curr_page - $offset;
                $to = $curr_page + $page - $offset - 1;

                if($page > $pages) {
                        $from = 1;
                        $to = $pages;
                } else {
                        if($from < 1) {
                                $to = $curr_page + 1 - $from;
                                $from = 1;
                                if(($to - $from) < $page && ($to - $from) < $pages) {
                                        $to = $page;
                                }
                        } elseif($to > $pages) {
                                $from = $curr_page - $pages + $to;
                                $to = $pages;
                                if(($to - $from) < $page && ($to - $from) < $pages) {
                                        $from = $pages - $page + 1;
                                }
                        }
                }
                $prepage = $curr_page - 1;
                $nextpage = $curr_page + 1;

                for($i = $from; $i <= $to; $i++) {
                        $sd = $i*$perpage;
                        if($i>=200){
                            $start=(($i-1)*$perpage)+1;
                        }else{
                            $start=$i;
                        }
                          $multipage .= "<div class=\"ome-stock-title ome-stock-list\" page=$i><span class=\"handler\"></span>序号".$start."-".$sd."入库商品明细</div>";

                }


        //}
        return $multipage;
    }

    function add_pos(){
        $obranch_pos = app::get('ome')->model("branch_pos");
        
        $branch_id = $_GET['branch_id'];
        $pos_value = $_GET['pos_value'];
        $product_id = $_GET['product_id'];
        $pos = $obranch_pos->dump(array('branch_id'=>$branch_id,'store_position'=>$pos_value),'pos_id');
        if(empty($pos)){
            $pos_data = array(
                'branch_id'=>$branch_id,
                'store_position'=>$pos_value
            );
            $result = $obranch_pos->save($pos_data);
            echo $pos_data['pos_id'];

        }else{
            echo '0';
        }
    }

    /**
     * 根据条码检查是否该货品(基础物料)存在
     * 
     * @param Int $po_ids
     * @param Int $barcode
     * @return Boolean/String
     */
    function get_po_info($po_ids='',$barcode='')
    {
        $basicMaterialLib    = kernel::single('material_basic_material');
        $libBranchProductPos    = kernel::single('ome_branch_product_pos');
        $barcode = $barcode ? $barcode : $_GET['barcode'];
        $po_id = $po_ids ? $po_ids :$_GET['po_id'];
        
        $oPo = app::get('purchase')->model("po");
        $oPo_items = app::get('purchase')->model("po_items");
        $po_items = $oPo_items->dump(array('po_id'=>$po_id,'barcode'=>$barcode),'*');

        //fixed by xiayuanjun save_barcode方法调用输出1导致页面显示错误信息的问题
        if(empty($po_items)){
            return false;
        }

        $po = $oPo->dump($po_id,'branch_id,operator');
        $po_items['operator'] = $po['$po'];
        $assign = $libBranchProductPos->get_pos($po_items['product_id'],$po['branch_id']);

        $po_items['entry_num'] = 1;#条码入库初始值为1

        if(empty($assign)){
            $pos_list = $libBranchProductPos->get_unassign_pos($po['branch_id']);
            $po_items['is_new']="true";
            $po_items['is_new_value']= "是";
        }else{
            $Po_items['is_new']="false";
            $po_items['is_new_value']="否";
            $pos_list = $assign;
        }
        //$pos_list = $assign;
        $po_items['is_new_value'] =  $po_items['in_num'] ? '否' : '是';
        $po_items['pos_list']=$pos_list;
        $product_id = $po_items['product_id'];
        
        $bMaterialRow    = $basicMaterialLib->getBasicMaterialExt($product_id);
        
        $po_items['unit'] = $bMaterialRow['unit'];
        $po_items['visibility'] = ($bMaterialRow['visibled'] == 1 ? true : false);
        
        $po_items['goods_bn'] = $bMaterialRow['material_bn'];


        //页面异步检测调用，返回json数据格式结果
        if (empty($po_ids)){
            //增加是否保质期物料的变量
            $basicMStorageLifeLib    = kernel::single('material_storagelife');
            $is_use_expire    = $basicMStorageLifeLib->checkStorageLifeById($product_id);

            //根据采购单获取已采购入库的批次信息
            $storageLifeBatch = $basicMStorageLifeLib->getStorageLifeBillById($po['branch_id'],$po_id, 1, $product_id);
            $po_items['instock_storagelife'] = $storageLifeBatch ? json_encode($storageLifeBatch) : '';

            $po_items['use_expire'] = $is_use_expire ? 1 : 0;
            $po_items['entry_num'] = $is_use_expire ? 0 : 1;
            $po_items['button'] = $is_use_expire ? '<a class="instock_sl" bm_id="'.$product_id.'" expire_bm_info="" style="color:#0066cc; text-decoration:none;" id="expire_bm_'.$product_id.'">关联保质期</a><input name="is_expire_bn[]" type="hidden" value="'.$product_id.'" />' : '-';

            echo json_encode($po_items);
        }else{
            //保存方法save_barcode调用，返回Boolean结果
            if ($po_items) return true;
            else return false;
        }

    }

    /**
     * 条码入库保存
     */
    function save_barcode(){
    	$pObj = app::get('purchase')->model("po");
        $po_id = $_POST['po_id'];
        $operator = $pObj->dump(array('po_id'=>$po_id),'operator');
        $_POST['operator'] = $operator['operator'];
        $gotourl = 'index.php?app=wms&ctl=admin_eo&act=Barcode_stock&p[0]='.$po_id.'&find_id='.$_POST['find_id'];
        $this->begin('');

        //异步获取单条条码的对应货品信息
        if (empty($_POST['submit_flag'])){
            if ($_POST['some_name']){
                $po_id = $_POST['po_id'];
                $barcode = $_POST['some_name'];
                $items = $this->get_po_info($po_id, $barcode);
                if ($items){
                    $msg = '加载成功';
                    $result = true;
                }else{
                    $msg = '没有找到货品';
                    $result = false;
                }
            }else{
                $msg = '请输入条码';
                $result = false;
            }
            $this->end($result, $msg, '', array('flag'=>'true'));
        }else{
            //正式的条码入库方式表单提交处理
            $oPo_items = app::get('purchase')->model("po_items");
            $oEo = app::get('purchase')->model("eo");
            $basicMaterialObj     = app::get('material')->model('basic_material');
            $basicMaterialConf    = app::get('material')->model('basic_material_conf');
            $basicMReceiptStorageLifeLib = kernel::single('material_receipt_storagelife');
            $basicMStorageLifeLib    = kernel::single('material_storagelife');
            $codeBaseLib = kernel::single('material_codebase');

            $entry_num = $_POST['entry_num'];
            $pos_name = $_POST['pos_name'];
            $branch_id = $_POST['branch_id'];
            $ids = $_POST['ids'];
            $expire_bm_ids = $_POST['is_expire_bn'];
            $expire_bm_arr = $_POST['expire_bm_info'];
            if (isset($_POST['arrival_no']) && preg_match("/[\x7f-\xff]/", $_POST['arrival_no'])) {
                $this->end(false, '到货单号不能包含中文', $gotourl);
            }
            //有保质期物料数据处理
            $all_expire_bm_arr = array();
            $has_expire_bn = false;
            if($expire_bm_ids){
                $has_expire_bn = true;
                $poObj = app::get('purchase')->model("po");
                $poBillInfo = $poObj->dump(array('po_id'=>$po_id),'po_bn');

                if($expire_bm_arr && $expire_bm_arr[0] != null){
                    foreach($expire_bm_arr as $expire_bm){
                        $tmp_expire_bm_arr = array();
                        $tmp_expire_bm_arr = json_decode($expire_bm,true);
                        if($tmp_expire_bm_arr){
                            foreach($tmp_expire_bm_arr as $k => $tmp_expire_bm)
                            {
                                if($k == 0) {
                                    $entry_num[$tmp_expire_bm['item_id']] = 0;
                                }
                                //如果保质期已存在，判断是否有效状态可操作
                                $storageLifeInfo = $basicMStorageLifeLib->getStorageLifeBatch($branch_id, $tmp_expire_bm['bm_id'], $tmp_expire_bm['expire_bn']);
                                if($storageLifeInfo){
                                    if($storageLifeInfo['status'] == 2){
                                        $this->end(false, '保质期条码已被关闭停用：'.$tmp_expire_bm['expire_bn'], $gotourl);
                                    }
                                }

                                $basicMInfo = $basicMaterialObj->dump(array('bm_id'=>$tmp_expire_bm['bm_id']), 'bm_id, material_name, material_bn,material_bn_crc32');
                                $basicMaterialConfInfo = $basicMaterialConf->dump(array('bm_id'=>$tmp_expire_bm['bm_id']), 'warn_day,quit_day');
                                //数组格式化批次货品的具体每个批次
                                $all_expire_bm_arr[] = array_merge($tmp_expire_bm,$basicMInfo,$basicMaterialConfInfo,array('branch_id'=>$branch_id,'bill_id'=>$po_id,'bill_bn'=>$poBillInfo['po_bn'],'bill_type'=>1,'bill_io_type'=>1));
                                $all_expire_ids[] = $basicMInfo['bm_id'];
                                $all_expire_bn_ids[$basicMInfo['bm_id']] = $basicMInfo['material_bn'];
                                //重新计算批次货品的入库数量总数
                                if(isset($entry_num[$tmp_expire_bm['item_id']])){
                                    $entry_num[$tmp_expire_bm['item_id']] += $tmp_expire_bm['in_num'];
                                }else{
                                    $entry_num[$tmp_expire_bm['item_id']] = $tmp_expire_bm['in_num'];
                                }
                            }
                        }
                    }
                }else{
                    $this->end(false, '未录入保质期信息', $gotourl);
                }
                
                if($entry_num){
                    foreach($entry_num as $k =>$val){
                        $_POST['entry_num'][$k] = $val;
                    }
                }

                foreach((array)$expire_bm_ids as $bm_id){
                    if(!in_array($bm_id,$all_expire_ids)){
                        $this->end(false, '物料：'.$all_expire_bn_ids[$bm_id].'的保质期信息没有录入', $gotourl);
                    }
                }
            }

            $timeout = array('autohide'=>5000);
            if (empty($ids)){
                $this->end(false, '没有任何商品入库，点击关闭页面退出当前入库操作', $gotourl, $timeout);
            }

            $ret = array();
            foreach ($ids as $id) {
                if ($entry_num[$id] == 0){
                    $this->end(false, '货品入库量不可为0', $gotourl);
                }
            }

            foreach($ids as $i){
               $Po_items=$oPo_items->dump(array('po_id'=>$po_id,'item_id'=>$i),'num,in_num');
                $p_entry_num = $Po_items['num']-$Po_items['in_num'];
                if($entry_num[$i]>$p_entry_num){
                   $this->end(false, '入库量大于可入库量', $gotourl);
                }
            }

            $msg = [];
            //保质期信息保存
            $is_save = $basicMReceiptStorageLifeLib->generate($all_expire_bm_arr,$msg);
            //如果有批次信息
            if(($has_expire_bn && $is_save) || !$has_expire_bn){
                if($all_expire_bm_arr) $_POST['all_expire_bm_arr'] = $all_expire_bm_arr;
                //更新采购入库单、更新库存数、生成出入库明细
                kernel::single('wms_eo')->save_eo($_POST);

               //事件触发，通知oms采购单入库 add by danny event notify
                kernel::single('wms_event_trigger_purchase')->inStorage($_POST,true);
                #$result = kernel::single('wms_eo')->notify_purchase($po_id,$_POST,'create');
                
                $this->end(true, '入库成功', $gotourl);
            }else{
                $error_msg = is_array($msg) ? implode('!',$msg) : '批次信息保存失败';
                $this->end(false, $error_msg, $gotourl);
            }

        }
    }

    /*打印入库单*/
    function printeo($eo_id)
    {
        $basicMaterialLib    = kernel::single('material_basic_material');
        $libBranchProductPos    = kernel::single('ome_branch_product_pos');
        
        $iostock_instance = kernel::service('taoguaniostockorder.iostockorder');
        $eo_de = $iostock_instance->getIso($eo_id);
        $eo['detail'] = $eo_de;
        $oBranch = app::get('ome')->model("branch");
        
        $oSupplier = app::get('purchase')->model("supplier");
        
        $Branch = $oBranch->dump($eo_de['branch_id'],'name');
        $supplier = $oSupplier->dump($eo_de['supplier_id'],'name');
        $eo['supplier_name'] = $supplier['name'];
        $eo['branch_name'] = $Branch['name'];
        
        $items = $iostock_instance->getIsoItems($eo_id);
        
        $product_cost = 0;
        if($items) {
            foreach($items as $k=>$v) {
                $productInfo = array();
                $items[$k]['store_position'] = $libBranchProductPos->get_product_pos($v['product_id'],$eo_de['branch_id']);
                
                $productInfo    = $basicMaterialLib->getBasicMaterialExt($v['product_id']);
                
                $items[$k]['barcode'] = $productInfo['barcode'];//读取商品条码
                $items[$k]['product_name'] = $productInfo['material_name'];
                $items[$k]['spec_info'] = $items[$k]['spec_info'] ? $items[$k]['spec_info'] : $productInfo['specifications'];
                $product_cost += $items[$k]['nums']*$items[$k]['price'];
                
                $items[$k]['unit'] = $productInfo['unit'];
            }
        }
        $this->pagedata['product_cost'] = $product_cost;

        $eo['items'] = $items;
        if($eo['detail']['memo']){
            
            if(!empty($eo['detail']['memo'])){
                foreach($eo['detail']['memo'] as $k => $v){
                    $arr[]= $v['op_content'];
                }
            }
        }
        #金额总计=商品总额+出入库费用
        $eo['detail']['amount'] = $eo['detail']['product_cost'] +$eo['detail']['iso_price'];

        $this->pagedata['eo'] = $eo;

        $this->pagedata['process_name'] = '入库';
        if($_GET['t'] == 0) {
            $this->pagedata['process_name'] = '出库';
        }

        # 改用新打印模板机制 chenping
        kernel::single('ome_print_otmpl')->printOTmpl($_GET['otmplId'],'pureo',$this);
    }

    function storage_life_instock()
    {
        $po_id = $_POST['po_id'];
        $bm_id = $_POST['bm_id'];
        $has_expire_bm_info = $_POST['has_expire_bm_info'] ? $_POST['has_expire_bm_info'] : 1;
        //$expire_bm_info_arr = $has_expire_bm_info ? json_decode($has_expire_bm_info,true) : '';

        if(empty($po_id) || empty($bm_id))
        {
            die('无效操作，请检查！');
        }
        
        $oPo = app::get('purchase')->model("po");
        $poItemsObj = app::get('purchase')->model("po_items");
        $basicMaterialObj     = app::get('material')->model('basic_material');#基础物料
        $basicMStorageLifeLib    = kernel::single('material_storagelife');
        $basicMaterialStorageLifeObj = app::get('material')->model('basic_material_storage_life');
        
        #采购信息
        $Po = $oPo->dump($po_id,'branch_id,supplier_id');
        
        //采购明细
        $poItems    = $poItemsObj->dump(array('po_id'=>$po_id, 'product_id'=>$bm_id), 'item_id,po_id, bn, num, in_num');
        if(empty($poItems))
        {
            die('没有找到对应的采购入库单信息');
        }
        
        //保质期配置信息
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
        $filter            = array('branch_id'=>$Po['branch_id'], 'bm_id'=>$bm_id, 'expiring_date|than'=>time());
        $storageLifeInfo   = $basicMaterialStorageLifeObj->getList('bmsl_id', $filter);
        $this->pagedata['exist_expire']        = ($storageLifeInfo ? 'true' : 'false');#标记
        
        $this->pagedata['has_expire_bm_info']          = $has_expire_bm_info;
        $this->pagedata['po_items']          = $poItems;
        $this->pagedata['time_from']         = date('Y-m-d', time());
        $this->pagedata['item']              = $row;
        $this->pagedata['material_conf']     = $material_conf;
        $this->page('admin/eo/storage_life_instock.html');
    }

    function do_storage_life_instock()
    {
        $po_id   = $_POST['po_id'];
        $bm_id    = $_POST['bm_id'];
        $item_id    = $_POST['item_id'];

        $expire_barcode   = $_POST['expire_barcode'];
        $expire_num       = $_POST['expire_num'];
        $production_date       = $_POST['production_date'];
        $date_type       = $_POST['date_type'];
        $guarantee_period       = $_POST['guarantee_period'];
        $expiring_date       = $_POST['expiring_date'];
        
        //生成保质期数据内容字符串
        $save_data = array();
        $count = 0;
        foreach ($expire_barcode as $key => $val)
        {
            $save_data[$key]['bm_id']            = $bm_id;
            $save_data[$key]['item_id']            = $item_id;
            $save_data[$key]['expire_bn']     = $val;#物料保质期编码
            $save_data[$key]['in_num']        = $expire_num[$key];#入库数量
            $save_data[$key]['production_date']    = $production_date[$key];#生产日期
            $save_data[$key]['date_type']    = $date_type[$key];
            $save_data[$key]['guarantee_period']    = $guarantee_period[$key];#保质期
            $save_data[$key]['expiring_date']    = $date_type[$key] == 'date' ? $expiring_date[$key] : '';#过期日期
            $count +=$save_data[$key]['in_num'];
        }

        $msg = json_encode($save_data);
        echo json_encode(array('code' => 'SUCC', 'msg' => $msg, 'count'=>$count));
        exit;
    }
    
    /*
     * 检查保质期物料是否存在
     */
    function isExistExpireBn()
    {
        $po_id    = $_POST['po_id'];
        $bm_id    = $_POST['bm_id'];
        $expire_bn        = $_POST['expire_bn'];
        $date_type_list   = array(1=>'day', 'month', 'year', 'date');
        
        if(empty($po_id) || empty($bm_id) || empty($expire_bn))
        {
            echo json_encode(array('code' => 'error', 'msg' => '无效操作'));
            exit;
        }
        
        $oPo    = app::get('purchase')->model("po");
        $basicMaterialStorageLifeObj    = app::get('material')->model('basic_material_storage_life');
        
        #采购信息
        $Po    = $oPo->dump($po_id,'branch_id,supplier_id');
        
        #保质期批次号
        $filter    = array('branch_id'=>$Po['branch_id'], 'bm_id'=>$bm_id, 'expire_bn'=>$expire_bn);
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
