<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class vop_ctl_admin_bill extends desktop_controller {

    function _views(){
        $billMdl = app::get('vop')->model('bill');
        $base_filter = array(
           
        );
        $sub_menu = array(
           
            0 => array('label'=>app::get('base')->_('全部'),'filter'=>array(),'optional'=>false),
            
            1 => array('label'=>app::get('base')->_('未确认'),'filter'=>array('status'=>'0'),'optional'=>false),
           
            2 => array('label'=>app::get('base')->_('已确认'),'filter'=>array('status'=>'1'),'optional'=>false),
            
            
        );

       
        foreach($sub_menu as $k=>$v){
            if (!IS_NULL($v['filter'])){
                $v['filter'] = array_merge($v['filter'], $base_filter);
            }
            
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $billMdl->viewcount($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=vop&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view='.$k;
        }

        return $sub_menu;
    }

    /**
     * index
     * @return mixed 返回值
     */
    public function index() {
        $actions = array();
        
        $actions[] = array(
            'label'=>app::get('ome')->_('单拉vop账单'),
            'href'=>"index.php?app=vop&ctl=admin_bill&act=sync",'target'=>'dialog::{width:690,height:500,title:\'单拉vop账单\'}"'

        );

        if($_GET['view'] ==2){

            $actions[] = array(
                'label' => '同步至账务账单',
                'submit' => $this->url.'&act=syncFinance',
                'target'=>'dialog::{width:600,height:200,title:\'批量对勾选的账单同步至财务吗\'}"',

            );
        }
        
        $params = array(
                'title'=>'货款账单',
                'use_buildin_set_tag'=>false,
                'use_buildin_filter'=>true,
                'use_buildin_export'=>true,
                'use_buildin_import'=>false,
                'use_buildin_recycle'=>false,
                'actions'=>$actions,
                'orderBy' => 'id DESC',
        );
        


        $this->finder('vop_mdl_bill', $params);
        
       

    }

    /**
     * exportTemplate
     * @return mixed 返回值
     */
    public function exportTemplate()
    {
        $row = [
            'ID', '商品编码', '商品条形码', '商品名称', '行类型名称', '数量', '金额'
        ];
        $id = (int) $_GET['id'];
        $data = app::get('console')->model('vopbill_amount')->getList('id,bn,barcode,product_name,detail_line_name,qty,amount',['bill_id'=>$id]);
        $lib = kernel::single('omecsv_phpexcel');
        $lib->newExportExcel($data, '帐单模板', 'xls', $row);
    }

   
   

    /**
     * sync
     * @param mixed $downloadType downloadType
     * @param mixed $shop_id ID
     * @return mixed 返回值
     */
    public function sync($downloadType = '', $shop_id = '')
    {
        
       $shop = app::get('ome')->model('shop')->getList('shop_id, name', ['node_type'=>'vop']);
        if(empty($shop)) {
            exit('缺少唯品会店铺');
        }
        
       
        $this->pagedata['shop'] = $shop;
      
        $this->pagedata['start_time'] = strtotime('-7 days');
        $this->pagedata['end_time'] = time();

        $this->pagedata['request_url'] = $this->url.'&act=do_sync';

        $this->display('admin/vop/download_vopbill.html');
    }


    /**
     * do_sync
     * @return mixed 返回值
     */
    public function do_sync() {
        $shop_id = $_POST['shop_id'];
        $bill_type = $_POST['bill_type'];
        $bill_number = $_POST['bill_number'];


        $start_time   = $_POST['start_time'].' '.$_POST['_DTIME_']['H']['start_time'].':'.$_POST['_DTIME_']['M']['start_time'].':00';
        $end_time     = $_POST['end_time'].' '.$_POST['_DTIME_']['H']['end_time'].':'.$_POST['_DTIME_']['M']['end_time'].':00';
        $pageNo = (int) $_POST['page_no'];
        //加判断
       

        if (strtotime($start_time) >= strtotime($end_time)) {
            echo json_encode(['total'=>0]);exit;
        }

        $ret = ['total'=>0,'succ'=>0,'fail'=>0];
        
        
        
        $billMdl = app::get('vop')->model('bill');
        $billObj = kernel::single('vop_bill');
        if ( in_array($bill_type ,array('items','discount') ) && empty($bill_number)){
            $ret['err_msg'] = '请填入账单号';
            $ret['fail'] += 1;
            exit;
        }
        if (in_array($bill_type ,array('items','discount') )){
            
            $bills = $billMdl->dump(array('bill_number'=>$bill_number),'*');
            if(!$bills){

                echo json_encode(['total'=>0,'err_msg'=>$bill_number.':账单号不存在']);
                exit;
            }

            if(!in_array($bills['status'],array('0')) ){
                echo json_encode(['total'=>0,'err_msg'=>$bill_number.':账单号状态不可以单拉']);
                exit;
            }
        }
       
        if($bill_type == 'items'){
            
            $filter = [
                'status' => '0',
                'sync_status' => '1',
                'last_modified|lthan' => (time() - 600),
                'id'                    =>  $bills['id'],
            ];
            $oldRow = $billMdl->db_dump($filter);

            if($oldRow) {
                $billMdl->update(['sync_status'=>'0'], ['id'=>$bills['id'], 'status'=>'0']);
            }
            $sdf = ['get_time' => strtotime($end_time), 'bill_number'=>$bills['bill_number'],'id'=>$bills['id'],'shop_id'=>$bills['shop_id']];

            $result = $billObj->getBillDetail($sdf,$msg);


        }else if($bill_type == 'discount'){

            $filter = [
                'status' => '0',
                'discount_sync_status' => '1',
                'last_modified|lthan' => (time() - 600),
                'id'                    =>  $bills['id'],
            ];
            $oldRow = $billMdl->db_dump($filter);

            if($oldRow) {
                $billMdl->update(['discount_sync_status'=>'0'], ['id'=>$bills['id'], 'status'=>'0']);
            }
            $sdf = ['get_time' => strtotime($end_time), 'bill_number'=>$bills['bill_number'],'id'=>$bills['id'],'shop_id'=>$bills['shop_id'],'force_sync'=>true];
            $result = $billObj->getBillDiscountDetail($sdf);

        }else if($bill_type == 'bills'){
            $startTime = strtotime($start_time);
            $endTime = strtotime($end_time);

            $result = $billObj->getBillNumber($startTime, $endTime, $shop_id);
        }

        list($rs, $msg) = $result;
 
        if($rs) {
            $ret['succ'] += 1;
        } else {
            $ret['fail'] += 1;
            $ret['err_msg'] = $msg;
        }

        echo json_encode($ret);exit;
    }


    /**
     * confirm
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function confirm($id) {
        $vopModel = app::get('vop')->model('bill');
        $bills = $vopModel->db_dump(['id'=>$id],'*');


        $this->pagedata['bills'] = $bills;

        $discountMdl = app::get('vop')->model('source_discount');

        $discounts = $discountMdl->db->selectrow("SELECT  sum(final_total_amount) as dis_amount FROM sdb_vop_source_discount WHERE bill_id=".$id." AND detail_line_type like '%DISCOUNT%'");


       

        $discounts['dis_amount'] = sprintf('%.2f',$discounts['dis_amount']);

       
        
        $this->pagedata['discounts'] = $discounts;

        $insures = $discountMdl->db->selectrow("SELECT sum(final_total_amount) as insure_amount FROM sdb_vop_source_discount WHERE bill_id=".$id." AND detail_line_type like '%INSURE%'");


       

        $insures['insure_amount'] = sprintf('%.2f',$insures['insure_amount']);

       
        $this->pagedata['insures'] = $insures;

        $details = kernel::single('vop_bill')->getDetail($id);
        $this->pagedata['details'] = $details;  

        $objMath    = kernel::single('eccommon_math');

        $total_amount = $objMath->number_plus(array($bills['cr_cust_amount'], $bills['dr_cust_amount'],$bills['other_amount']));
       
        $total_amount = $objMath->number_plus(array($total_amount,$discounts['dis_amount'],$insures['insure_amount']));
     
        $total_amount = $objMath->number_plus(array($total_amount,$details['reship_amount']));
       
        $total_amount = $objMath->number_plus(array($total_amount,$details['refund_amount']));
       
     
        $this->pagedata['total_amount'] = $total_amount;

        $poMdl = app::get('vop')->model('po');

        $pos = $poMdl->db->selectrow("select sum(amount) as total_amount from sdb_vop_po where bill_id=".$id."");
        
        $this->pagedata['pos'] = $pos;
        $this->singlepage('admin/vop/bill_confirm.html');
    }

    /**
     * doConfirm
     * @return mixed 返回值
     */
    public function doConfirm() {
        $this->begin($this->url);
        $bill_id = (int) $_POST['bill_id'];
        $vbModel = app::get('vop')->model('bill');
        $oldRow = $vbModel->db_dump(['id'=>$bill_id],'id,bill_number,status');
        if($oldRow['status'] != '0') {
            $this->end(false, '账单已经被确认');
        }
        $rs = $vbModel->update(['status'=>'1','confirm_time'=>time()], ['id'=>$bill_id, 'status'=>'0']);
        if(is_bool($rs)) {
            $this->end(false, '账单确认失败');
        }
           
        //推送sap
        kernel::single('vop_po')->push($bill_id);
        
        $this->end(true, '操作成功');
    }


    /**
     * 批量推送
     */
    public function syncFinance()
    {
        @ini_set('memory_limit','512M');
        set_time_limit(0);
        
        $billMdl = app::get('vop')->model('bill');
        
        $ids = $_POST['id'];
        
        //check
        if($_POST['isSelectedAll'] == '_ALL_'){
            die('不能使用全选功能,每次最多选择1条!');
        }
        
        if(empty($ids)){
            die('请选择需要操作的单据!');
        }
        
        if(count($ids) > 1){
            die('每次最多只能选择1条!');
        }
        
        //data
        $dataList = $billMdl->getList('id', array('id'=>$ids, 'status'=>array('1')));
        if(empty($dataList)){
            die('没有可撤消的发货单!');
        }
        
        $ids = array_column($dataList, 'id');
        
        $this->pagedata['GroupList'] = json_encode($ids);
        
        $this->pagedata['request_url'] = $this->url .'&act=ajaxSyncfinance';
        
        //调用desktop公用进度条(第4个参数是增量传offset,否则默认一直为0)
        parent::dialog_batch('vop_mdl_bill', false, 50, 'incr');
    }
    
    /**
     * ajaxSyncfinance
     * @return mixed 返回值
     */
    public function ajaxSyncfinance()
    {
       
        $billMdl = app::get('vop')->model('bill');
        $retArr = array(
                'itotal' => 0,
                'isucc' => 0,
                'ifail' => 0,
                'err_msg' => array(),
        );
        
        //获取发货单号
        parse_str($_POST['primary_id'], $postdata);
        if(!$postdata){
            echo 'Error: 请先选择单据';
            exit;
        }
        
        //filter
        $filter = $postdata['f'];
        $offset = intval($postdata['f']['offset']);
        $limit = intval($postdata['f']['limit']);
        
        if(empty($filter)){
            echo 'Error: 没有找到查询条件';
            exit;
        }
        
        //data
        $dataList = $billMdl->getList('id,status', $filter, $offset, $limit);
        
        //check
        if(empty($dataList)){
            echo 'Error: 没有获取到单据';
            exit;
        }
        
        //count
        $retArr['itotal'] = count($dataList);
        
        //list
        foreach ($dataList as $key => $v)
        {
            $id = $v['id'];
            
            if(!in_array($v['status'], array('1'))){
                continue;
            }
            
         
            list($rs,$msg) = kernel::single('vop_po')->push($id);
            if ($rs == true) {
                //succ
                $retArr['isucc'] += 1;
                
            }else{
                //error
                $retArr['ifail'] += 1;
                $retArr['err_msg'][] = '推送失败';
                
             
            }
        }
        
        echo json_encode($retArr),'ok.';
        exit;
    }



    /**
     * 获取Item
     * @param mixed $id ID
     * @return mixed 返回结果
     */
    public function getItem($id) {
        set_time_limit(0);
        $rObj = app::get('vop')->model('bill');
        $main = $rObj->db_dump($id);

        
        kernel::single('vop_bill')->getBillDetail($main);
    
        kernel::single('vop_bill')->getBillDiscountDetail($main);
    

        kernel::single('vop_bill')->getItemSourceDetail($main);
    
        
        
        $this->splash('success', $this->url, '操作完成');
    }

    /**
     * test
     * @return mixed 返回值
     */
    public function test() {

       
        set_time_limit(0);

        ini_set('display_errors', 'on');
        error_reporting(E_ALL);

        kernel::single('vop_autotask_timer_bill')->getBillDetail();

        kernel::single('vop_autotask_timer_bill')->getBillDiscountDetail();
        kernel::single('vop_autotask_timer_bill')->getItemSourceDetail();
        
    }


    
}
