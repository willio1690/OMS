<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_ctl_settlement extends desktop_controller{
    var $flag = 'index';
    var $name = "实收实退单";

    public function index(){
        $this->title = '实收实退单';
        $this->flag = 'index';

        $is_export = kernel::single('desktop_user')->has_permission('finance_export');#增加销售应收单导出权限

        $base_filter = array();
        $this->base_filter = $base_filter;

        $actions = array();
        switch ($_GET['view']) {
            case 1:
                $actions['zq'] = array (
                    'label'  => '匹配账期',
                    'submit'   => $this->url.'&act=matchReport&view='.$_GET['view'],
                    'target' => 'dialog::{width:600,height:300,title:\'匹配账期\'}'
                );
                /**$actions['hx'] = array(
                    'label' => '再次核销',
                    'submit' => $this->url . '&act=verifyAgain',
                    'target' => "dialog::{width:500,height:200,title:'再次核销'}",
                );**/
                break;
            default:
                break;
        }

        $params = array(
            'title'=>$this->title ,
            'use_buildin_export'=>$is_export,
            'use_buildin_recycle'=>false,
            'use_view_tab'=>true,
            'actions' => $actions,
            'use_buildin_selectrow'=>true,
            'use_buildin_filter'=>true,
            'finder_aliasname'=>'ar_unsale',
            'finder_cols'=>'bill_bn,status,channel_name,fee_obj,member,trade_time,order_bn,fee_type,fee_item,money,bill_type',
            'base_filter' => $base_filter,
            'orderBy'=> 'bill_id desc',
        );
        $this->finder('finance_mdl_bill',$params);
    }

    public function _views(){
        
        $method_name = '_views_'.$this->flag;
        if(method_exists($this, $method_name))
        {
            return $this->$method_name();
        }else{
            return array();
        }

    }

    public function _views_index(){
        $arObj = $this->app->model('bill');
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'),'filter'=>array(),'addon'=>'showtab','optional'=>false),
            1 => array('label'=>app::get('base')->_('未匹配账期'),'filter'=>array('monthly_item_id'=>0),'addon'=>'showtab','optional'=>false),
        );
        return $sub_menu;
    }


    // 导出设置页
    // public function export($view){
    //     $oFunc = kernel::single('financebase_func');
    //     $this->pagedata['platform_list'] = $oFunc->getShopPlatform();

    //     $this->pagedata['view'] = $view;

    //     $this->pagedata['time_from'] = date('Y-m-01', strtotime(date("Y-m-d")));
    //     $this->pagedata['time_to'] = date('Y-m-d', strtotime("$_POST[time_from] +1 month -1 day"));
       
    //     $this->pagedata['finder_id'] = $_GET['finder_id'];
    //     $this->display('settlement/export.html');
    // }

    public function doBillExport($platform_type,$time_from,$time_to,$view=0){
        $oFunc = kernel::single('financebase_func');
        $platform_list = $oFunc->getShopPlatform();

        $filter = array('trade_time|between'=>array(strtotime($time_from),strtotime($time_to)),'fee_obj'=>$platform_list[$platform_type]);

        $sub_menu = $this->_views_index();

        $filter = array_merge($filter,$sub_menu[$view]['filter']);

        $file_name = sprintf("%s平台%s实收实退单[%s]",$platform_list[$platform_type],$sub_menu[$view]['label'],date('Y-m-d'));

        $this->doExport($filter,$file_name,$platform_type);

    }

    // 导出未匹配订单号
    public function exportUnMatch(){

        $oFunc = kernel::single('financebase_func');

        $this->pagedata['platform_list'] = $oFunc->getShopPlatform();
       
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->display('settlement/export_unmatch.html');
    }

    public function doUnMatchExport($platform_type = 'alipay'){

        $oFunc = kernel::single('financebase_func');
        $platform_list = $oFunc->getShopPlatform();

        $filter = array('order_bn'=>'','fee_obj'=>$platform_list[$platform_type]);

        $file_name = sprintf("%s平台未匹配订单号[%s]",$platform_list[$platform_type],date('Y-m-d'));

        $this->doExport($filter,$file_name,$platform_type);
 
    }

    /*public function doExport($filter,$file_name,$platform_type = 'alipay'){

        set_time_limit(0);

        $oFunc = kernel::single('financebase_func');
        $mdlBill = app::get('finance')->model('bill');
    
        $page_size = $oFunc->getConfig('page_size');

        $class_name = sprintf("financebase_data_bill_%s",$platform_type);


        $shop_list = financebase_func::getShopList();

        $shop_list = array_column($shop_list,null,'shop_id');



        if (ome_func::class_exists($class_name) && $instance = kernel::single($class_name)){

            $csv_title = $instance->getTitle();
            $csv_title['shop_id'] = '所属店铺';
            $csv_title['bill_bn'] = '单据编号';
            $csv_title['order_bn'] = '订单号';

            header('Content-Type: application/vnd.ms-excel;charset=utf-8');
            header("Content-Disposition:filename=" . $file_name . ".csv");

            $fp = fopen('php://output', 'a');
            $csv_title_value = array_values($csv_title);
            foreach ($csv_title_value as &$v) $v = $oFunc->strIconv($v,'utf-8','gbk');
            fputcsv($fp, $csv_title_value);

            $id = 0;
            while (true) {

                $data = $mdlBill->getExportData($filter,$page_size,$id);

                if($data){
                    foreach ($data as &$v) {
                        $tmp = array();
                        $v['shop_id'] = isset($shop_list[$v['shop_id']]) ? $shop_list[$v['shop_id']]['name'] : '';
                        foreach ($csv_title as $title_key => $title_val) {
                            $tmp[] = isset($v[$title_key]) ? $oFunc->strIconv($v[$title_key],'utf-8','gbk')."\t" : '';
                        }
                        fputcsv($fp, $tmp);
                    }
                }else{
                    break;
                }

            }

            exit;

        }

    }*/

    

    // 导入未匹配订单号
    public function importUnMatch(){

        $oFunc = kernel::single('financebase_func');

        $this->pagedata['platform_list'] = $oFunc->getShopPlatform();
       
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->display('settlement/import_unmatch.html');
    }

    public function doUnMatchImport()
    {
        @ini_set('memory_limit', '512M');
        $this->begin('index.php?app=finance&ctl=settlement&act=index&view=3');

        $platform_type = $_POST['platform_type'] ? $_POST['platform_type'] : 'alipay';

        if( $_FILES['import_file']['name'] && $_FILES['import_file']['error'] == 0 ){
            $file_type = substr($_FILES['import_file']['name'],strrpos($_FILES['import_file']['name'],'.')+1);
            if(in_array($file_type, array('csv','xls','xlsx'))){

                $ioType = kernel::single('financebase_io_'.$file_type);
                $oProcess = kernel::single('financebase_data_bill_'.$platform_type);
                $oFunc = kernel::single('financebase_func');
                /*if(!$oProcess->checkFile($_FILES['import_file']['tmp_name'],$file_type)){
                    $this->end(false, app::get('base')->_('上传文件数据不对'));
                }

                //临时文件生成后往ftp服务器迁移
                $storageLib = kernel::single('taskmgr_interface_storage');
                $move_res = $storageLib->save($_FILES['import_file']['tmp_name'], md5($_FILES['import_file']['name'].time()).'.'.$file_type, $remote_url);
                
                if(!$move_res)
                {
                    $this->end(false, app::get('base')->_('文件上传失败'));
                }else{
                    $worker = "financebase_data_task.doAssign";
                    $params = array();
                    $params['shop_id'] = $_POST['shop_id'];
                    $params['shop_type'] = $type;
                    $params['task_name'] = $_FILES['import_file']['name'];
                    $params['file_type'] = $file_type;
                    $params['file_name'] = $remote_url;
                    $oFunc->addTask('分派对账单导入',$worker,$params);

                    $this->end(true, app::get('base')->_('上传成功 已加入队列 系统会自动跑完队列'));
                }*/


                $page_size = $oFunc->getConfig('page_size');

                $file_name = $_FILES['import_file']['tmp_name'];
                $file_info = $ioType->getInfo($file_name); 
                $total_nums = $file_info['row'];
                $page_nums = ceil($total_nums / $page_size);

                for ($i=1; $i <= $page_nums ; $i++) {
                    $offset = ($i - 1) * $page_size;
                    $data = $ioType->getData($file_name,0,$page_size,$offset,true); 
                    $oProcess->updateOrderBn($data);
                }

                $this->end(true, app::get('base')->_('更新成功'));
            }else{
                $this->end(false, app::get('base')->_('不支持此文件'));
            }

        }else{
            $this->end(false, app::get('base')->_('没有导入成功'));
        }
    }


    //核销
    public function detailVerification(){
        $billObj = &app::get('finance')->model('bill');
        $bill_id = $_GET['bill_id'];
        $bill_data = kernel::single('finance_bill')->get_bill_by_bill_id($bill_id,'bill_id');
        $ar_data = kernel::single('finance_bill')->get_ar_by_bill_id($bill_id,'order_bn');

        // financebase_func::dd($ar_data);

        $this->pagedata['bill_data'] = $bill_data;
        $this->pagedata['ar_data'] = $ar_data;
        $this->pagedata['bill_id'] = $bill_id;

        $this->pagedata['finder_id'] = $_GET['finder_id'];
        if(isset($_GET['flag']) && $_GET['flag'] == 'replace'){
            $this->pagedata['replace'] = true;
        }else{
            $this->pagedata['replace'] = false;
        }
        // $html = $this->fetch('settlement/verificate_detail.html');
        // echo $html;
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->singlepage('settlement/verificate_detail.html');
    }

    public function confirmVerification(){
        $data = json_decode($_COOKIE['VERIFICATION_MSG'],1);
        $this->pagedata['info'] = $data;
        $this->page('settlement/verificate_confirm.html');
    }

    public function checkVerificate(){
        $res = kernel::single('finance_bill')->checkVerificate($_POST);
        $res = json_encode($res);
        setcookie('VERIFICATION_MSG', $res );
        echo $res;
    }


     //确认核销
    public function doVerificate(){
        $this->begin('');
        $res = kernel::single('finance_bill')->doManVerificate($_POST);
        // echo json_encode($res);
        $this->end(true, app::get('base')->_('核销成功'));
    }


    //  重新设置订单号
    public function resetOrderBn($bill_id)
    {
        $bill_info = app::get('finance')->model("bill")->getList('order_bn,bill_id,credential_number,bill_bn,money',array('bill_id'=>$bill_id,'status'=>0),0,1);
        $this->pagedata['bill_info'] = $bill_info[0];
        $this->singlepage("settlement/reset_orderbn.html");
    }

    //  保存设置订单号
    public function saveOrderBn()
    {
        $this->begin('index.php?app=finance&ctl=settlement&act=index');
        $oBill = app::get('finance')->model("bill");
        $oBaseBill = app::get('financebase')->model("bill");
     
        $bill_id = intval($_POST['bill_id']);
        $order_bn = trim($_POST['order_bn']);
        if(!$bill_id)
        {
            $this->end(false, "ID不存在");
        }

        $bill_info = app::get('finance')->model("bill")->getList('order_bn,bill_bn,unique_id,channel_id',array('bill_id'=>$bill_id,'status|lthan'=>2),0,1);

        if(!$bill_info)
        {
            $this->end(false, "流水单不存在");
        }

        if(!$order_bn)
        {
            $this->end(false, "订单号不存在");
        }

        $bill_info = $bill_info[0];
        $bill_bn = $bill_info['bill_bn'];

        if($order_bn == $bill_info['order_bn'])
        {
            $this->end(false, "订单号没有改变");
        }


        if($oBill->update(array('order_bn'=>$order_bn),array('bill_id'=>$bill_id,'status'=>0)))
        {
            $oBaseBill->update(array('order_bn'=>$order_bn),array('unique_id'=>$bill_info['unique_id'],'shop_id'=>$bill_info['channel_id']));
            $this->end(true, app::get('base')->_('保存成功'));
        }
        else
        {
            $this->end(false, app::get('base')->_('保存失败'));
        }
    }

    // 导入强制核销流水单
    public function importVerification()
    {

        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->display('settlement/import_verification.html');
    }

    public function doVerificationImport()
    {
        $this->begin('index.php?app=finance&ctl=settlement&act=index&view=1');

        if( $_FILES['import_file']['name'] && $_FILES['import_file']['error'] == 0 ){
            $file_type = substr($_FILES['import_file']['name'],strrpos($_FILES['import_file']['name'],'.')+1);
            if(in_array($file_type, array('csv','xls','xlsx'))){

                 //临时文件生成后往ftp服务器迁移
                $storageLib = kernel::single('taskmgr_interface_storage');
                $move_res = $storageLib->save($_FILES['import_file']['tmp_name'], md5($_FILES['import_file']['name'].time()).'.'.$file_type, $remote_url);
                
                if(!$move_res)
                {
                    $this->end(false, app::get('base')->_('文件上传失败'));
                }else{
    
                    $mdlQueue = app::get('financebase')->model('queue');
                    $queueData = array();
                    $queueData['queue_mode'] = 'forceVerification';
                    $queueData['create_time'] = time();
                    $queueData['queue_name'] = sprintf("强制核销流水单");
                    $queueData['queue_data']['shop_type'] = $type;
                    $queueData['queue_data']['task_name'] = basename($_FILES['import_file']['name']);
                    $queueData['queue_data']['file_type'] = $file_type;
                    $queueData['queue_data']['op_name']   = kernel::single('desktop_user')->get_name();
                    $queueData['queue_data']['remote_url']= $remote_url;

                    $queue_id = $mdlQueue->insert($queueData);
                    financebase_func::addTaskQueue(array('queue_id'=>$queue_id),'forceverification');

                    $this->end(true, app::get('base')->_('上传成功 已加入队列 系统会自动跑完队列'));
                }

            }else{
                $this->end(false, app::get('base')->_('不支持此文件'));
            }

        }else{
            $this->end(false, app::get('base')->_('没有导入成功'));
        }
    }
    
    public function matchReport() {
        $filter = array(
            'monthly_item_id' => '0',
        );
        $filter = array_merge($filter, $_POST);
        $list = app::get('finance')->model('bill')->getList('bill_id', $filter);
        $GroupList = array_column($list, 'bill_id');
        $this->pagedata['request_url'] = $this->url.'&act=doMatchReport';
        $this->pagedata['itemCount'] = count($GroupList);
        $this->pagedata['GroupList'] = json_encode($GroupList);
        $this->pagedata['maxNum']    = 100;
        parent::dialog_batch();
    }

    public function doMatchReport() {
        $itemIds = explode(',',$_POST['primary_id']);

        if (!$itemIds) { echo 'Error: 缺少单据';exit;}

        $retArr = array(
            'itotal'  => count($itemIds),
            'isucc'   => 0,
            'ifail'   => 0,
            'err_msg' => array(),
        );
        $monthlyId = [];
        foreach($itemIds as $itemId) {
            list($rs, $rsData) = kernel::single('finance_monthly_report_items')->dealBillMatchReport($itemId);
        
            if($rs) {
                $monthlyId[$rsData['monthly_id']] = $rsData['monthly_id'];
                $retArr['isucc'] += 1;
            } else {
                $retArr['ifail'] += 1;
                $retArr['err_msg'][] = $rsData['msg'];
            }
        }
        if($monthlyId) {
            finance_monthly_report::updateMonthlyAmount(['monthly_id'=>$monthlyId]);
        }
        echo json_encode($retArr),'ok.';exit;
    }

    public function verifyAgain() {
        $model = app::get('finance')->model('bill');
        $pageData = array(
            'billName' => '实收实退单',
            'request_url' => $this->url.'&act=dealVerifyAgain',
            'maxProcessNum' => 100,
            'close' => true
        );
        $this->pagedata['notice'] = '重置状态后，半个小时内系统重新跑！';
        $this->selectToPageRequest($model, $pageData);
    }

    public function dealVerifyAgain() {
        $bill_id = explode(';', $_POST['ajaxParams']);
        $retArr = array(
            'total' => count($bill_id),
            'succ' => 0,
            'fail' => 0,
            'fail_msg' => array()
        );
        app::get('finance')->model('bill')->update(['is_check'=>0], array('bill_id'=>$bill_id,'status|noequal'=>2,'is_check'=>2));
        $retArr['succ'] = $retArr['total'];
        echo json_encode($retArr);
    }
}