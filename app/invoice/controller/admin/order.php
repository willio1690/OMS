<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 订单发票管理
 */
class invoice_ctl_admin_order extends desktop_controller
{
    public function __construct($app)
    {
        parent::__construct($app);
    }
    
    function index()
    {
        $base_filter = array('disabled'=>'false');

        $params = array(
            'title'=>'订单发票列表',
            'use_buildin_set_tag' =>false,
            'use_buildin_filter'  =>true,
            'use_buildin_tagedit' =>true,
            'use_buildin_export'  =>true,
            'use_buildin_import'  =>false,
            // 'allow_detail_popup'  =>true,
            'use_buildin_recycle' =>false,
            'use_view_tab'        =>true,
            'finder_cols'         =>'tax_rate,invoice_no,ship_tax,ship_bank,ship_bank_no,print_num,remarks,ship_area,ship_addr,ship_tel',
            'base_filter'         => $base_filter,
        );
        
        $organization_permissions = kernel::single('desktop_user')->get_organization_permission();
        if ($organization_permissions) {
            $params['base_filter']['org_id'] = $organization_permissions;
        }
        if (in_array($_GET['view'], [1, 2, 10])) {
            $actions = array(
                array(
                    'label' => '批量操作',
                    'group' => array(
                        array('label' => '批量作废', 'submit'=>'index.php?app=invoice&ctl=admin_order&act=batchCancel','target'=>'dialog::{width:690,height:200,title:\'批量作废\'}')
                    )
                )
            );
            
            if (kernel::single('desktop_user')->has_permission('make_invoice')) {
                $actions[0]['group'][] = array(
                    'label'  => '批量开票',
                    'submit' => 'index.php?app=invoice&ctl=admin_order&act=batchBill',
                    'target' => 'dialog::{width:690,height:200,title:\'批量开票\'}'
                );
            }
        }
        
//        if ($_GET['view'] == 1) {
//            $actions[] = array(
//                'label'  => '发票运单号导入',
//                'href'   => 'index.php?app=invoice&ctl=admin_order&act=waybillImport',
//                'target' => "dialog::{width:500,height:500,title:'发票运单号导入'}",
//            );
//        }
//
//        if($_GET['view'] == 4){
//            $actions[] = array(
//                'label'  => '购方信息导入',
//                'href'   => 'index.php?app=invoice&ctl=admin_order&act=invoiceImport',
//                'target' => "dialog::{width:500,height:500,title:'发票导入'}",
//            );
//        }

        //增加同步状态结果
        if ($_GET['view'] == 5){
            $actions[0]['label'] = '批量操作';
            $actions[0]['group'] = array(
                array(
                    'label'=>'批量同步开票结果',
                    'submit'=>"index.php?app=invoice&ctl=admin_order&act=batchSyncEResult",
                    'target'=>'dialog::{width:690,height:200,title:\'批量同步开票结果\'}"'
                ),
            );
        }

        //只有“专票信息 tab”，才显示当前指定模板导出按钮
        if (in_array($_GET['view'], [3, 4, 7])){
            $params['use_buildin_export'] = false;  //不显示系统导出按钮
            $btnPrefix                    = $_GET['view'] == 8 ? '金3冲红' : '专票开票';
            $actions[]                    = [
                'label'  => $btnPrefix . '导出',
                'submit' => 'index.php?app=invoice&ctl=admin_order&act=exportVatInvoice&action=export&finder_aliasname=default&view='.$_GET['view'],
                'target' => 'dialog::{width:450,height:210,title:\'' . app::get('desktop')->_($btnPrefix . '导出') . '\'}',
            ];
            $actions[]                    = [
                'label'  => $btnPrefix . '导入模板',
                'href' => 'index.php?app=invoice&ctl=admin_order&act=exportVatTemplate&view='.$_GET['view'],
                'target' => '_blank',
            ];
            $actions[]                    = [
                'label'  => $btnPrefix . '导入',
                'href' => 'index.php?app=omecsv&ctl=admin_import&act=main&ctler=invoice_mdl_order_vatInvoiceExport&add='.$this->app->app_id,
                'target' => 'dialog::{width:500,height:250,title:\'' . app::get('desktop')->_($btnPrefix. '导入') . '\'}',
            ];
    
        }

        if(in_array($_GET['view'],array('2','4')) ){
            $actions[] = array(
                'label' => 'POS操作',
                'group' => array(
                    array(
                        'label'   => '同步开票结果',
                        'submit'  => 'index.php?app=invoice&ctl=admin_order&oper=batch&act=batchUpload
',
                        'confirm' => '你确定要对勾选的发票结果同步pos吗?',
                        'target'=>'dialog::{width:700,height:500,title:\'同步开票结果\'}"'
                    ),
                    
                ),
            );
        }
        // 金税三期 显示冲红导入导出
        if (in_array($_GET['view'], [8])) {
            $params['use_buildin_export'] = false;  //不显示系统导出按钮
            $btnPrefix = '金3冲红';
            $actions[] = [
                'label' => $btnPrefix . '导出',
                'submit' => 'index.php?app=invoice&ctl=admin_order&act=exportGolden3Cancel&action=export&finder_aliasname=default&view=' . $_GET['view'],
                'target' => 'dialog::{width:450,height:210,title:\'' . app::get('desktop')->_($btnPrefix . '导出') . '\'}',
            ];
            $actions[] = [
                'label' => $btnPrefix . '导入',
                'href' => 'index.php?app=omecsv&ctl=admin_import&act=main&ctler=invoice_mdl_order_golden3CancelExport&add=' . $this->app->app_id,
                'target' => 'dialog::{width:500,height:250,title:\'' . app::get('desktop')->_($btnPrefix . '导入') . '\'}',
            ];

        }

        //增加同步状态结果
        if ($_GET['view'] == 9) {
            $actions[0]['label'] = '批量操作';
            $actions[0]['group'] = array(
                array(
                    'label' => '批量同步冲红申请单结果',
                    'submit' => "index.php?app=invoice&ctl=admin_order&act=batchSyncRedApplyResult",
                    'target' => 'dialog::{width:690,height:200,title:\'批量同步冲红申请单结果\'}"'
                ),
            );
        }
        $actions[]                    = [
            'label'  => '发票备注导入',
            'href'   => $this->url . '&act=execlImportDailog&p[]=memo',
            'target' => 'dialog::{width:760,height:400,title:\'发票备注导入\'}',
        ];
        $actions[]                    = [
            'label'  => '合并开票',
            'submit'   => $this->url . '&act=merge_invoice&finder_vid='.$_GET['finder_vid'],
//            'target' => 'dialog::{width:1200,height:400,title:\'合并开票\'}',
        ];
        
        $params['actions'] = $actions;
        
        $this->finder('invoice_mdl_order', $params);
    }
    

    function invoiceImport(){
        echo $this->page('admin/invoice_import.html');
    }

    //执行发票导入操作
    function doInvoiceImport()
    {
        // 读取文件
        try {
            $importLIb = kernel::single("invoice_import_buyer");
            $msg = $importLIb->process();

            $msg = implode('<br/>', $msg);
            $finder_id = $_GET['finder_id'];
            header("content-type:text/html; charset=utf-8");
            echo <<<JS
                <script>
                    alert("上传成功");
                    parent.\$E('#import-form .error').set('html',"部分导入失败：<br/>$msg");


                    if ("$msg") {
                        parent.\$E('#import-form .error').show();
                    } else {
                        parent.\$E('#import-form').getParent('.dialog').retrieve('instance').close();

                        if (window.finderGroup && window.finderGroup["$finder_id"]) {
                            window.finderGroup["$finder_id"].refresh();
                        }else{
                            parent.location.reload();
                        }
                    }

                </script>
JS;

        } catch (Exception $e) {
            $this->splash('error', null, '导入失败:' . $e->getMessage());
        }
    }

    //导出发票模板
    function exportInvoiceTemplate(){
        $row = app::get('invoice')->model('order')->getInvoiceTemplateColumn();
        $lib = kernel::single('omecsv_phpexcel');
        $lib->newExportExcel(null, "发票导入模板-" . date('Ymd'), 'xlsx', array_keys($row));
    }

    function waybillImport(){
        echo $this->page('admin/waybill_import.html');
    }

    //执行发票运单号导入操作
    function doWaybillImport()
    {
        try {
            $importLIb = kernel::single("invoice_import_waybill");
            $msg = $importLIb->process();

            $msg = implode('<br/>', $msg);
            $finder_id = $_GET['finder_id'];
            header("content-type:text/html; charset=utf-8");
            echo <<<JS
                <script>
                    alert("上传成功");
                    parent.\$E('#import-form .error').set('html',"部分导入失败：<br/>$msg");


                    if ("$msg") {
                        parent.\$E('#import-form .error').show();
                    } else {
                        parent.\$E('#import-form').getParent('.dialog').retrieve('instance').close();

                        if (window.finderGroup && window.finderGroup["$finder_id"]) {
                            window.finderGroup["$finder_id"].refresh();
                        }else{
                            parent.location.reload();
                        }
                    }

                </script>
JS;

        } catch (Exception $e) {
            $this->splash('error', null, '导入失败:' . $e->getMessage());
        }
    }

    //导出发票运单号模板
    function exportWaybillTemplate()
    {
        $row = app::get('invoice')->model('order')->getWaybillTemplateColumn();
        $lib = kernel::single('omecsv_phpexcel');
        $lib->newExportExcel(null, "发票运单号导入模板-" . date('Ymd'), 'xlsx', array_keys($row));
    }

    function batchBill()
    {
        if (!$_POST['id']) {
            die('暂不支持全选');
        }
        
        $this->pagedata['GroupList']   = json_encode($_POST['id']);
        $this->pagedata['request_url'] = 'index.php?app=invoice&ctl=admin_order&act=doBatchBill';

        parent::dialog_batch();
    }
    
    function doBatchBill()
    {
        $primary_id = explode(',', $_POST['primary_id']);
        if (!$primary_id) { echo 'Error: 请先选择发票';exit;}

        $retArr = array(
            'itotal'  => count($primary_id),
            'isucc'   => 0,
            'ifail'   => 0,
            'err_msg' => array(),
        );

        $order_id_map = array();
        $list = $this->app->model('order')->getList('id,order_id', array('id'=>$primary_id));
        foreach($list as $v){
            $order_id_map[$v['id']] = $v['order_id'];
        }
        
        //一单一单处理
        foreach ($primary_id as $id)
        {
            $param = array('id'=>$id, 'order_id'=>$order_id_map[$id]);
            
            $rs = kernel::single('invoice_process')->billing($param,'man',$error_msg);
            if($rs){
                $retArr['isucc']++;
            }else{
                $retArr['ifail']++;
                $retArr['err_msg'][] = $error_msg;
            }
        }
        
        echo json_encode($retArr),'ok.';
        exit;
    }
    
    function batchCancel($id = '')
    {
        if ($id) $_POST['id'][] = $id;

        if (!$_POST['id']) {
            die('暂不支持全选');
        }
        
        $this->pagedata['GroupList']   = json_encode($_POST['id']);
        $this->pagedata['request_url'] = 'index.php?app=invoice&ctl=admin_order&act=doBatchCancel';
        $this->pagedata['custom_html'] = $this->fetch('admin/red_reason.html');

        parent::dialog_batch();
    }

    function doBatchCancel()
    {
        $primary_id = explode(',', $_POST['primary_id']);
        if (!$primary_id) { echo 'Error: 请先选择发票';exit;}

        $retArr = array(
            'itotal'  => count($primary_id),
            'isucc'   => 0,
            'ifail'   => 0,
            'err_msg' => array(),
        );

        //一单一单处理
        foreach ($primary_id as $id) {
            $param = array('id'=>$id,'invoice_action_type'=>$_POST['invoice_action_type']);
            $rs = kernel::single('invoice_process')->cancel($param,"invoice_list");
            if($rs){
                $retArr['isucc']++;
            }else{
                $retArr['ifail']++;
                $retArr['err_msg'][] = '作废发票失败';
            }
        }
        
        echo json_encode($retArr),'ok.';exit;
    }

    /**
     * 分类导航
     */
    function _views()
    {
        $mdl_order = $this->app->model('order');
        
        $base_filter = array();
        $sub_menu = array(
             0=>array('label'=>__('全部'),'filter'=>$base_filter),
             1=>array('label'=>__('待开电票'),'filter'=>array('mode'=>'1','is_make_invoice'=>'1','is_status'=>'0'),'optional'=>false),
             2=>array('label'=>__('已开电票'),'filter'=>array('mode'=>'1','is_status'=>'1'),'optional'=>false),
             3=>array('label'=>__('待开专票'),'filter'=>array('type_id'=>'1','is_make_invoice'=>'1','mode'=>'0','is_status|noequal'=>'2'),'optional'=>false),
             4=>array('label'=>__('已开专票'),'filter'=>array('type_id'=>'1','is_status'=>'1','mode'=>'0'),'optional'=>false),

             5=>array('label'=>__('同步中'),'filter'=>array('sync'=>array('1','4','7','9')),'optional'=>false),
             7=>array('label'=>__('待冲专票'),'filter'=>array('is_make_invoice'=>array('2')),'optional'=>false),
             8=>array('label'=>__('金3冲红'),'filter'=>array('sync'=>'10'),'optional'=>false),
             9=>array('label'=>__('冲红申请单确认中'),'filter'=> array('sync' => '7'),'optional'=>false),
             10 => array ('label' => __('开蓝冲红失败'), 'filter' => array('sync' => ['5','2','8'],'is_status' => ['0','1']), 'optional' => false),
        );

        foreach($sub_menu as $k => $v)
        {
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $mdl_order->viewcount($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=invoice&ctl='.$_GET['ctl'].'&act=index&view='.$k;
        }
        // 金3冲红固定显示
        $sub_menu[8]['addon'] = 'showtab';

        return $sub_menu;
    }
    
    //作废
    function doCancel()
    {
        $url = 'javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();';
        $id = $_GET["id"];
        if(!$id){
            $this->splash('error',$url,'操作出错，不存在此条发票记录，请重新操作。');
        }

        $arr_cancel = array("id" => $id);

        $result = kernel::single('invoice_process')->cancel($arr_cancel,"invoice_list");
        if($result){
            if(intval($result["mode"]) == 1){
                //这里提示打开电子发票接口是否缺少必要参数
                if(!empty($result["arr_hint"])){
                    $this->splash('error',$url,"电子发票冲红失败：".implode("，", $result["arr_hint"])."。");
                }else{
                    $hint = "电子发票冲红操作已执行。";
                }
            }else{
                $hint = "作废纸质发票成功。";//纸质发票
            }
            
            $this->splash('success',$url,$hint);
        }else{
            $this->splash('error',$url,'作废发票失败。');
        }
    }
    
    //执行开票
    function doBilling()
    {
        $url = 'javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();';
        $id = $_GET["id"];
        if(!$id){
            $this->splash('error',$url,'操作出错，不存在此条发票记录，请重新操作。');
        }
        
        //execute
        $error_msg = '';
        $arr_billing = array(
            "id" => $id,
        );
        $result = kernel::single('invoice_process')->billing($arr_billing, 'man', $error_msg);
        if(!$result){
            $this->splash('error', $url, '开票失败：'. $error_msg);
        }
        
        if($result['rsp'] != 'succ'){
            $this->splash('error', $url, "电子发票开蓝票失败：". $result['error_msg']);
        }
        
        //电子发票
        if($result['mode'] == 1){
            $hint = "电子发票开蓝票操作已执行。";
        }else{
            $hint = "纸质发票开票成功。";
        }
        
        $this->splash('success',$url,$hint);
    }
    
    //编辑页面展示
    function edit()
    {
       $id = intval($_GET["id"]);
       if(!$id){
          return false;
       }
       
       //获取发票信息
       $mdlInOrder = app::get('invoice')->model('order');
       $rs_invoice_order = $mdlInOrder->dump(array("id"=>$id),"*");
       $memo = @unserialize($rs_invoice_order['memo']);
       $rs_invoice_order['memo'] = is_array($memo) ? end($memo) : ['op_content'=>''];
       $this->pagedata["invoice_order"] = $rs_invoice_order;

       //获取明细信息
       $invoiceItemMdl = app::get('invoice')->model('order_items');
       $invoice_order_items = $invoiceItemMdl->getList('*',array('id'=>$id,'is_delete'=>'false'));
       
       //合并发票的明细处理
       if ($rs_invoice_order['invoice_type'] == 'merge') {
           $invoice_order_items = kernel::single('invoice_order')->showMergeInvoiceItems($invoice_order_items);
       }
       
       $this->pagedata['invoice_order_items'] = $invoice_order_items;

       //获取发票内容
       $mdlInContent = app::get('invoice')->model('content');
       $this->pagedata["invoice_content"] = $mdlInContent->getList();
       
      
       $this->pagedata["act"] = "doEdit";
       $this->pagedata['title'] = "编辑发票";
       $this->display('admin/order_editor.html');
    }
    
    //编辑提交操作
    function doEdit()
    {
        $this->begin('index.php?app=invoice&ctl=admin_order&act=index');

        $id = $_POST["item"]["id"];
        if(!$id){
          return false;
        }
        
        $check_result = kernel::single('invoice_check')->checkEdit($id);
        if(!$check_result){
          $this->end(false, '编辑失败，此发票记录必须是未开票状态。');
        }
        
        list($result,$msg) = kernel::single('invoice_process')->edit($_POST["item"]);
        if($result){
          $this->end(true, '编辑发票信息成功。');
        }else{
          $this->end(false, $msg);
        }
    }
    
    //新建发票信息发票页面展示
    function addNewSame()
    {
       $id = intval($_GET["id"]);
       if(!$id){
         return false;
       }
       
       //获取发票信息
       $mdlInOrder = app::get('invoice')->model('order');
       $rs_invoice_order = $mdlInOrder->dump(array("id"=>$id),"*");
        $memo = @unserialize($rs_invoice_order['memo']);
        $memo = $memo ?: [];
        $rs_invoice_order['memo'] = end($memo);
       $this->pagedata["invoice_order"] = $rs_invoice_order;

        //获取明细信息
        $invoiceItemMdl = app::get('invoice')->model('order_items');
        $invoice_order_items = $invoiceItemMdl->getList('*',array('id'=>$id,'is_delete'=>'false'));
        //合并发票的明细处理
        if ($rs_invoice_order['invoice_type'] == 'merge') {
            $invoice_order_items = kernel::single('invoice_order')->showMergeInvoiceItems($invoice_order_items);
        }
        $this->pagedata['invoice_order_items'] = $invoice_order_items;

       //获取发票内容
       $mdlInContent = app::get('invoice')->model('content');
       $this->pagedata["invoice_content"] = $mdlInContent->getList();
      
       $this->pagedata["act"] = "doAddNewSame";
       $this->pagedata['title'] = "新建相似发票";

       $this->display('admin/order_editor.html');
    }

    //新建发票信息提交操作
    function doAddNewSame()
    {
        $this->begin('index.php?app=invoice&ctl=admin_order&act=index');
        $id = $_POST["item"]["id"];
        if(!$id){
          return false;
        }
        $data = kernel::single('invoice_order')->formatAddData($_POST["item"]);
        list($result,$msg) = kernel::single('invoice_process')->newCreate($data,'add_new_same');

        if($result){
         $this->end(true, '新建类似发票信息成功。');
        }else{
         $this->end(false, $msg);
        }
    }
    
    /**
     * 新建改票信息页面展示
     * @Author: xueding
     * @Vsersion: 2022/10/24 下午6:04
     * @return bool
     */
    function addChangeTicket()
    {
        $invoiceOrderLib     = kernel::single('invoice_order');
        $id = intval($_GET["id"]);
        if(!$id){
            return false;
        }
        //获取发票信息
        $mdlInOrder = app::get('invoice')->model('order');
        $rs_invoice_order = $mdlInOrder->dump(array("id"=>$id),"*");
        if ($rs_invoice_order['changesdf']) {
            $rs_invoice_order = json_decode($rs_invoice_order['changesdf'],1);
        }
        $memo = @unserialize($rs_invoice_order['memo']);
        $memo = $memo ?: [];
        $rs_invoice_order['memo'] = end($memo);
        $this->pagedata["invoice_order"] = $rs_invoice_order;
        $action = 'doAddChangeTicket';
        
        if ($_GET['type'] && $_GET['type'] == 'checkChangeTicket') {
            $action = 'doCheckChangeTicket';
        }
        $this->pagedata["act"] = $action;
        //获取明细信息
        $invoiceItemMdl = app::get('invoice')->model('order_items');
        $invoice_order_items = $invoiceItemMdl->getList('*',array('id'=>$id,'is_delete'=>'false'));
        //合并发票的明细处理
        if ($rs_invoice_order['invoice_type'] == 'merge') {
            $invoice_order_items = kernel::single('invoice_order')->showMergeInvoiceItems($invoice_order_items);
            $invoiceOrderLib->updateMergeInvoiceItems($rs_invoice_order,$invoice_order_items);
        }
        foreach ($invoice_order_items as $key => $val) {
            $item_id                                    = $val['item_id'];
            $invoice_order_items[$key]['specification'] = isset($rs_invoice_order['specification'][$item_id]) ? $rs_invoice_order['specification'][$item_id] : $val['specification'];
            $invoice_order_items[$key]['unit']          = isset($rs_invoice_order['unit'][$item_id]) ? $rs_invoice_order['unit'][$item_id] : $val['unit'];
        }
        $this->pagedata['invoice_order_items'] = $invoice_order_items;
    
        //获取发票内容
        $mdlInContent = app::get('invoice')->model('content');
        $this->pagedata["invoice_content"] = $mdlInContent->getList();
        $this->pagedata["title"] = '改票信息';
        
        $this->display('admin/order_editor.html');
    }
    
    /**
     * 保存改票信息
     * @Author: xueding
     * @Vsersion: 2022/10/24 下午6:06
     * @return bool
     */
    function doAddChangeTicket()
    {
        $this->begin('index.php?app=invoice&ctl=admin_order&act=index');
        $id = $_POST["item"]["id"];
        if(!$id){
            return false;
        }
        list($result,$msg) = kernel::single('invoice_process')->addChangeTicketData($_POST["item"]);
        
        if($result){
            $this->end(true, '新建改票信息成功。');
        }else{
            $this->end(false, $msg);
        }
    }
    
    /**
     * 暂时不用手动冲红自动执行app/erpapi/lib/invoice/request/invoice.php：：415行
     * 根据改票信息创建新发票
     * @Author: xueding
     * @Vsersion: 2022/10/25 下午4:57
     * @return bool
     */
    function doCheckChangeTicket()
    {
        $this->begin();
        $id = $_POST["item"]["id"];
        $order_id = $_POST["item"]["order_id"];
        if(!$id || !$order_id){
            return false;
        }
        $invoiceOrderMdl = app::get('invoice')->model('order');
        $oldInvoiceOrder = $invoiceOrderMdl->dump(array("id"=>$id));
        if (!$oldInvoiceOrder) {
            return false;
        }
        $params = array_merge($oldInvoiceOrder,$_POST['item']);
        $params['action_type'] = 'doCheckChangeTicket';
        $result = kernel::single('invoice_process')->create($params,"invoice_list_add_same");

        if($result){
            $this->end(true, '改票确认成功。');
        }else{
            $this->end(false, '改票确认失败。');
        }
    }
    
    //预览电子发票
    function preview()
    {
        if(!$_GET["id"] || !$_GET["type"]){
            echo '<script>window.close();</script>';
            return;
        }
        
        $mdlInOrder = app::get('invoice')->model('order');
        $rs_invoice = $mdlInOrder->dump(array("id"=>$_GET["id"]));
        $get_channel_info = app::get('invoice')->model('channel')->get_channel_info($rs_invoice['shop_id']);
        
        #店铺没有电子发票渠道配置，直接false;
        if(empty($get_channel_info))return false;
        $rs_invoice['channel_id'] = $get_channel_info['channel_id'];
        $rs_invoice['channel_extend_data'] = isset($get_channel_info['channel_extend_data'])?$get_channel_info['channel_extend_data']:'';
        
        $einvoice_url = kernel::single('invoice_electronic')->getApiEinvoiceUrl($rs_invoice,$_GET["type"]);
        if($einvoice_url){
            echo '<script>window.location.href="'.$einvoice_url.'";</script>';
            exit;
        }else{
            echo '<script>alert("预览失败");</script>';
            exit;
        }
    }
    
    //淘宝系  上传天猫
    function doUploadTmall()
    {
        $step = intval($_GET['step']) ? intval($_GET['step']) : 1; //当前到第几步
        $total_step = intval($_GET["total_step"]); //全部步数
        $id = $_POST['invoice_id']; //发票记录主键id
        
        //获取invoice order信息
        $mdlInOrder = app::get('invoice')->model('order');
        $rs_invoice = $mdlInOrder->dump(array("id"=>$id));
        $rs_invoice['amount'] = kernel::single('invoice_func')->get_invoice_amount($rs_invoice);
        $rs_invoice["einvoice_type"] = "blue"; //prepare接口和回流天猫upload接口都用到
        $log_mes_type = "蓝票";
        $billing_type = "1";
        if(intval($rs_invoice["is_status"]) == 2 && intval($rs_invoice["sync"]) == 6){
            $rs_invoice["einvoice_type"] = "red";
            $log_mes_type ="红票";
            $billing_type = "2";
        }
        
        $order_setting = kernel::single('invoice_func')->get_order_setting($rs_invoice['shop_id'],1);
        
        //电子发票没有渠道配置，直接false;
        if(empty($order_setting)){
           return false;
        }
        $channel_shop_id = $order_setting[0]['shop_id'];
        
        //获取invoice item明细
        $mdlInvoiceElIt = app::get('invoice')->model('order_electronic_items');
        $rs_invoice_item = $mdlInvoiceElIt->dump(array("id"=>$rs_invoice["id"],"billing_type"=>$billing_type));
        
        $opObj = app::get('ome')->model('operation_log');
        
        $result = array('status'=>'running');
        
        //更新对应记录明细表字段条件
        $mdlInOrderElIt = app::get('invoice')->model('order_electronic_items');
        $filter_arr = array("id"=>$id,"billing_type"=>1);
        if($rs_invoice["einvoice_type"] == "red"){
            $filter_arr["billing_type"] = 2;
        }
        
        //第一步 先打prepare接口
        if($step == 1){
            //判断是否更新天猫状态成功过
            if(intval($rs_invoice_item["update_tmall_status"]) == 1){
                //未成功更新
                $rs_invoice["invoice_action_type"] = intval($_POST["invoice_action_type"]); //prepare接口用到 注意当发票prepare接口已经成功调用后 此值为0 也不会再次调用prepare接口了
                $rs_invoice = kernel::single('invoice_electronic')->getEinvoiceSerialNo($rs_invoice,$billing_type);
                $einvoice_prepare_rs = kernel::single('invoice_event_trigger_einvoice')->einvoicePrepare($channel_shop_id,$rs_invoice);
                if($einvoice_prepare_rs["rsp"] == "succ"){
                    //更新上传天猫状态字段 状态
                    $update_arr = array("update_tmall_status"=>2);
                    $mdlInOrderElIt->update($update_arr,$filter_arr);
                    $msg_prepare = $log_mes_type.'的天猫状态更新成功。';
                }else{
                    $msg_prepare = $log_mes_type.'的天猫状态更新失败。';
                    //获取返回的失败msg
                    if($einvoice_prepare_rs["rsp"] == "fail"){
                        $err_msg = @json_decode($einvoice_prepare_rs['err_msg'],true);
                        if($err_msg["error_response"]["sub_msg"]){
                            $msg_prepare .= "(".$err_msg["error_response"]["sub_msg"].")";
                        }
                    }
                    $prepare_fail = true;
                }
                
                //记录电子发票的天猫状态更新操作日志
                $opObj->write_log('einvoice_prepare_tmall@invoice', $id, $msg_prepare);
                
                //prepare失败
                if($prepare_fail){
                    $this->splash('error','',$msg_prepare.'请查看日志原因或重试。');
                }
            }else{
                //update_tmall_status字段为2 是已成功更新过了 直接走下一步
            }
        }
        
        //第二步  获取serial_no 打上传天猫接口
        if($step >= $total_step){
            //上传天猫必须通过打get接口来获取开票流水号serial_no
            $info_return_rs = kernel::single('invoice_event_trigger_einvoice')->getEinvoiceInfo($channel_shop_id,$rs_invoice);
            $einvoice_data_arr = json_decode($info_return_rs["data"],true);
            $rs_invoice["serial_no"] = $einvoice_data_arr["serial_no"];
            
            //获取开票流水号serial_no成功
            if($rs_invoice["serial_no"]){
                //上传天猫接口
                $einvoice_upload_rs = kernel::single('invoice_event_trigger_einvoice')->uploadTmall($channel_shop_id,$rs_invoice);
                if($einvoice_upload_rs["rsp"] == "succ"){
                    //上传天猫成功 更新发票明细表upload_tmall_status为2
                    $update_arr = array("upload_tmall_status"=>2);
                    $mdlInOrderElIt->update($update_arr,$filter_arr);
                    $msg_upload = $log_mes_type.'上传天猫成功。';
                }else{
                    $msg_upload = $log_mes_type.'上传天猫失败。';
                    //获取返回的失败msg
                    if($einvoice_upload_rs["rsp"] == "fail" && $einvoice_upload_rs["err_msg"]){
                        $msg_upload .= "(".$einvoice_upload_rs["err_msg"].")";
                    }
                    $upload_fail = true;
                }
            }else{
                $get_return_serial_no_fail = true;
                $msg_upload = $log_mes_type.'上传天猫失败。(获取开票流水号失败)';
            }
            
            //记录上传回流天猫操作日志
            $opObj->write_log('einvoice_upload_tmall@invoice', $id, $msg_upload);
            //上传失败 因为成功通过get接口获取开票流水号
            if($get_return_serial_no_fail){
                $this->splash('error','','获取开票流水号失败。请查看日志原因或重试。');
            }
            //上传失败
            if($upload_fail){
                $this->splash('error','','上传天猫失败。请查看日志原因或重试。');
            }
            //成功完成
            $result['status'] = 'complete';
            $result['data']['rate'] = '100';
        }else{
            //步骤中
            $result['data']['rate'] =  $step / $total_step * 100;
        }
        
        echo json_encode($result);exit;
    }
    
    //上传天猫进度弹窗
    public function uploadTmallExpire()
    {
        if($_GET["invoice_action_type"]){
            $this->pagedata["invoice_action_type"] = 1; //只有第一次开蓝票此值是1
        }
        
        //获取是否是否成功更新过天猫上的发票状态
        $mdlInvoiceElIt = app::get('invoice')->model('order_electronic_items');
        $rs_invoice_item = $mdlInvoiceElIt->dump(array("id"=>$_GET["id"],"billing_type"=>$_GET["billing_type"]));
        
        $this->pagedata['update_tmall_status'] = $rs_invoice_item["update_tmall_status"]; //非第一次开蓝票 判断如果update_tmall_status为1显示作废原因等选项
        $this->pagedata['invoice_id'] = $_GET["id"];
        $this->pagedata['total_step'] = 2; //二步  1.prepare接口  2.get接口获取开票流水号serial_no并上传天猫接口
        $this->display('admin/upload_tmall_expire.html');
    }
    
    /**
     * 批量同步开票结果
     */
    public function batchSyncEResult()
    {
        $this->pagedata['request_url'] = 'index.php?app=invoice&ctl=admin_order&act=ajaxSyncEResult';

        $_POST['mode'] = '1';
        
        $id = array();
        $invOrderMdl = app::get('invoice')->model('order');
        if($_POST['isSelectedAll'] == '_ALL_') {
            $invOrderMdl->filter_use_like = true;
        }

        foreach ($invOrderMdl->getList('id',$_POST) as $value) {
            $id[] = $value['id'];
        }

        $this->pagedata['GroupList'] = json_encode($id);
        $this->pagedata['maxNum']    = 5;

        parent::dialog_batch();
    }

    public function ajaxSyncEResult()
    {
        $primaryIds = explode(',', $_POST['primary_id']);
        $retArr = array(
            'itotal'  => count($primaryIds),
            'isucc'   => 0,
            'ifail'   => 0,
            'err_msg' => array(),
        );

        $invEleItemMdl = app::get('invoice')->model('order_electronic_items');

        foreach($invEleItemMdl->getList('item_id,invoice_status',array('id'=>$primaryIds)) as $value) {
            if ($value['invoice_status'] == '0') {
                continue;
            }
            list($result,$errmsg) = kernel::single('invoice_event_trigger_einvoice')->getEinvoiceCreateResult($value['item_id']);

            if($result == 'succ') {
                $retArr['isucc']++;
                if ($errmsg) {
                    $retArr['err_msg'][] = $errmsg;
                }
            } else {
                $retArr['ifail']++;
                $retArr['err_msg'][] = $value['order_bn'].'同步失败：'.$errmsg;
            }
        }

        echo json_encode($retArr),'ok.';exit;
    }

    /**
     * 重新生成
     */
    public function regenResult($item_id)
    {
        $this->begin('javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();');

        $itemMdl = app::get('invoice')->model('order_electronic_items');
        $item = $itemMdl->db_dump($item_id);

        if ($item['url']) {
            $affect_row = $itemMdl->update(array('url'=>''),array('item_id'=>$item_id));
            if ($affect_row !== 1) $this->end(false,'更新URL异常');
        }

        $invMdl = app::get('invoice')->model('order');
        $invoice = $invMdl->db_dump($item['id']);

        if (!in_array($invoice['sync'], array('6','3'))) {
            $this->end(false,'开票未完成，不能操作');
        }
        $sync = $invoice['sync'] == '6' ? '4' : '1';

        $affect_row = $invMdl->update(array('sync'=>$sync),array('id'=>$invoice['id']));

        if ($affect_row !== 1) $this->end(false,'更新数据异常');

        list($result,$errmsg) = kernel::single('invoice_event_trigger_einvoice')->getEinvoiceCreateResult($item_id,false);

        $this->end($result == 'succ'?true:false,$errmsg);
    }
    
    /**
     * 批量上传电子发票
     **/
    public function batchUpload()
    {
        $this->pagedata['request_url'] = 'index.php?app=invoice&ctl=admin_order&act=ajaxUpload';

        $eOrderMdl = app::get('invoice')->model('order');
        $eOrderMdl->filter_use_like = true;

        $filter         = $_POST;
        $filter['sync'] = array(3,6);

        //$filter['mode'] = 1;

        if($_GET['oper']!='batch'){
            //$filter['mode'] = 1;
        }
        


        if ($_GET['id']) $filter['id'] = $_GET['id'];
       

        $id = array();
        foreach($eOrderMdl->getList('id',$filter) as $val){
            $id[] = $val['id'];
        }

        $this->pagedata['GroupList'] = json_encode($id);

        parent::dialog_batch();
    }

    /**
     * 开票上传
     **/
    public function ajaxUpload()
    {
        $primary_id = explode(',', $_POST['primary_id']);
        
        if (!$primary_id) { echo 'Error: 请先选择开票订单';exit;}
        
        $retArr = array(
            'itotal'  => 0,
            'isucc'   => 0,
            'ifail'   => 0,
            'err_msg' => array(),
        );
        
        $eItemMdl = app::get('invoice')->model('order_electronic_items');
        
        foreach ($eItemMdl->getList('item_id,invoice_no,billing_type',array('id' => $primary_id),0,-1,'create_time asc') as $value) {
        
            $result = kernel::single('invoice_event_trigger_einvoice')->upload($value['item_id'],true);
        
            if ($result['rsp'] == 'succ') {
                $retArr['isucc']++;
            } else {
                $retArr['ifail']++;
                $msg = $result['err_msg'] ? $result['err_msg'] : $result['msg'];
                $retArr['err_msg'][] = sprintf('发票号[%s]上传%s失败:%s',$value['invoice_no'],$value['billing_type']==1?'蓝票':'红票',$msg);
            }
        
            $retArr['itotal']++;
        }
        
        echo json_encode($retArr),'ok.';
        exit;
    }
    
    public function showSensitiveData($id,$type='order')
    {
        $invoice = array();
        if ($id) {
            $invoice = app::get('invoice')->model('order')->db_dump($id, 'shop_id,shop_type,order_bn,ship_addr,ship_tel,ship_bank_no,ship_bank,ship_tax');
            
            // 处理加密
            $invoice['encrypt_body'] = kernel::single('ome_security_router',$invoice['shop_type'])->get_encrypt_body($invoice, $type);
        }
        
        $this->splash('success',null,null,'redirect',$invoice);
    }

    /**
     * 专票信息导出
     * @authors 胡渊 <huyuan@shopex.cn>
     * @date 2022-10-25 16:25:58
     */
    public function exportVatInvoice()
    {
        $params                       = [
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'    => false,
            'use_buildin_recycle'    => false,
            'use_buildin_import'     => false,
            'use_buildin_filter'     => true,
        ];
        $_POST['_io_type'] = 'xls';
        $params['use_buildin_export'] = true;
        $this->finder('invoice_mdl_order_vatInvoiceExport', $params);
    }
    
    public function exportVatTemplate()
    {
        $btnPrefix                    = $_GET['view'] == 8 ? '金3冲红' : '专票开票';
        $invoiceVatMdl   = app::get('invoice')->model('order_vatInvoiceExport');
        $row = $invoiceVatMdl->exportTemplate();
        $lib = kernel::single('omecsv_phpexcel');
        $lib->newExportExcel(null, $btnPrefix.'导入模板', 'xls', $row);
    }
    
    /**
     * 保存回寄物流
     *
     * @return void
     * @author 
     **/
    public function saveReturnLogi()
    {
        $this->begin();

        if (!$_POST['id']) {
            $this->end(false, '请选择开票订单');
        }

        if (!$_POST['return_logi_no'] && !$_POST['return_logi_name']) {
            $this->end(false, '回寄物流不能为空');
        }

        $rs = app::get('invoice')->model('order')->update([
            'return_logi_name' => $_POST['return_logi_name'],
            'return_logi_no'    => $_POST['return_logi_no'],
        ], ['id' => $_POST['id']]);

        $this->end($rs);
    }
    
    /**
     * 查看快照
     */
    public function show_history($log_id)
    {
        $logObj       = app::get('ome')->model('operation_log');
        //日志
        $goodslog = $logObj->dump($log_id, 'memo');
        $content     = unserialize($goodslog['memo']);
    
        $invoice = $content['invoice'];
        $memo = @unserialize($invoice['memo']);
        $invoice['memo'] = $memo;
        $this->pagedata['invoice_order'] = $invoice;
        $this->pagedata['invoice_order_items'] = $content['invoice_order_items'];
        $this->pagedata['ome_orders'] = $content['order'];
        $this->pagedata['act'] = 'history';
        $this->singlepage('admin/order_editor.html');
    }
    
    /**
     * 预览发票pdf文件
     * @Author: xueding
     * @Vsersion: 2023/5/15 下午6:00
     * @param $id
     */
    public function show_preview_pdf($id)
    {
        $fileLib    = kernel::single('base_storager', 'invoice');
        $file_path = $fileLib->getFile($id, null);
        header('Content-type: application/pdf');
        header('filename=' . $file_path);
        $file_path ? readfile($file_path) : '';
    }
    
    //合并发票页面展示
    function merge_invoice()
    {
        
        $id = $_POST["id"];
        if(!$id){
            die('缺少合并数据');
        }
        if (count($id) <= 1) {
            die('合并发票数据不能少于一条');
        }
        //获取发票信息
        $mdlInOrder = app::get('invoice')->model('order');
        $rs_invoice_order = $mdlInOrder->getList('*',array("id"=>$id));
        $invoiceAmount = $freightAmount = $taxAmount = 0;
        $orderBns = [];
        
        if (count(array_unique(array_column($rs_invoice_order,'mode'))) > 1) {
            die('发票类型不一致暂不支持合并');
        }
        foreach ($rs_invoice_order as $key => $val) {
            if ($val['is_status'] == '2') {
                die('已作废发票不能进行合并开票操作');
            }
            $invoiceAmount += $val['amount'];
            $freightAmount += $val['cost_freight'];
            $taxAmount     += $val['cost_tax'];
            if (strpos($val['order_bn'], ',')) {
                $orderBns = array_merge($orderBns, explode(',',$val['order_bn']));
            } else {
                $orderBns[] = $val['order_bn'];
            }
        }

        $showInvoiceInfo = current($rs_invoice_order);
        
        $showInvoiceInfo['amount'] = $invoiceAmount;
        $showInvoiceInfo['cost_freight'] = $freightAmount;
        $showInvoiceInfo['cost_tax'] = $taxAmount;
        $showInvoiceInfo['order_bn'] = implode(',',array_unique($orderBns));
        $showInvoiceInfo['is_make_invoice'] = '0';
        $showInvoiceInfo['invoice_type'] = 'merge';
        
        $memo = @unserialize($showInvoiceInfo['memo']);
        $memo = $memo ?: [];
        $showInvoiceInfo['memo'] = end($memo);

        //获取明细信息
        $invoiceItemMdl = app::get('invoice')->model('order_items');
        $invoice_order_items = $invoiceItemMdl->getList('*',array('id'=>$id,'is_delete'=>'false'));
        $itemData = [];
        foreach ($invoice_order_items as $key => $val) {
            if (!$itemData[$val['bn']]) {
                $itemData[$val['bn']] = $val;
            }else{
                $itemData[$val['bn']]['amount'] += $val['amount'];
                $itemData[$val['bn']]['quantity'] += $val['quantity'];
            }
        }
        $showInvoiceInfo['id'] = implode(',',$id);
        
        $this->pagedata["invoice_order_list"]  = $rs_invoice_order;
        $this->pagedata["invoice_order"]       = $showInvoiceInfo;
        $this->pagedata['invoice_order_items'] = $itemData;
        
        //获取发票内容
        $mdlInContent = app::get('invoice')->model('content');
        $this->pagedata["invoice_content"] = $mdlInContent->getList();
        
        
        $this->pagedata["act"] = 'doMergeInvoice';
        $this->display('admin/order_merge_invoice.html');
    }
    
    /**
     * 合并发票处理
     * @Author: xueding
     * @Vsersion: 2023/6/2 下午5:04
     * @return bool
     */
    function doMergeInvoice()
    {
        $this->begin('index.php?app=invoice&ctl=admin_order&act=index');
        
        $id = $_POST["item"]["id"];
        if(!$id){
            return false;
        }

        list($check_result,$msg) = kernel::single('invoice_check')->checkMergeInvoice($id);
        if(!$check_result){
            $this->end(false, $msg);
        }
        
        list($result,$msg) = kernel::single('invoice_process')->addMergeInvoice($_POST);
        if($result){
            $this->end(true, '创建合并发票信息成功。');
        }else{
            $this->end(false, $msg);
        }
    }


    /**
     * 批量同步开票结果
     */
    public function batchSyncRedApplyResult()
    {
        $this->pagedata['request_url'] = 'index.php?app=invoice&ctl=admin_order&act=ajaxSyncRedApplyResult';

        $_POST['mode'] = '1';
        $_POST['sync'] = '7';

        $id = array();
        $invOrderMdl = app::get('invoice')->model('order');
        if ($_POST['isSelectedAll'] == '_ALL_') {
            $invOrderMdl->filter_use_like = true;
        }

        foreach ($invOrderMdl->getList('id', $_POST) as $value) {
            $id[] = $value['id'];
        }

        $this->pagedata['GroupList'] = json_encode($id);
        $this->pagedata['maxNum'] = 5;

        parent::dialog_batch();
    }

    public function ajaxSyncRedApplyResult()
    {
        $primaryIds = explode(',', $_POST['primary_id']);
        $retArr = array(
            'itotal' => count($primaryIds),
            'isucc' => 0,
            'ifail' => 0,
            'err_msg' => array(),
        );

        foreach ($primaryIds as $primaryId){
            $result = kernel::single('invoice_event_trigger_redapply')->sync($primaryId);
            if($result['rsp'] == 'succ'){
                $retArr['isucc']++;
            }else{
                $retArr['ifail']++;
                $retArr['err_msg'][] = "id:" . $primaryId . '同步失败：' . $result['msg'];
            }
        }

        echo json_encode($retArr), 'ok.';
        exit;
    }

    /**
     * 金三红冲导出
     */
    public function exportGolden3Cancel()
    {
        $params = [
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => false,
            'use_buildin_import' => false,
            'use_buildin_filter' => true,
        ];
        $_POST['_io_type'] = 'xls';
        $params['use_buildin_export'] = true;
        $this->finder('invoice_mdl_order_golden3CancelExport', $params);
    }
}
