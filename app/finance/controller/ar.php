<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_ctl_ar extends desktop_controller{
	var $name = "销售应收单";

    /**
     * index
     * @return mixed 返回值
     */
    public function index(){
        $is_export = kernel::single('desktop_user')->has_permission('finance_export');#增加销售应收单导出权限
        $actions = [];
        if($_GET['view'] == '1') {
            $actions['zq'] = array (
                'label'  => '匹配账期',
                'submit'   => $this->url.'&act=matchReport&view='.$_GET['view'],
                'target' => 'dialog::{width:600,height:300,title:\'匹配账期\'}'
            );
        }
        
        $this->finder('finance_mdl_ar',array(
                'title'=>app::get('finance')->_('应收应退单') ,
                'actions'=>$actions,
                'use_buildin_export'=>$is_export,
                'use_buildin_recycle'=>false,
                'use_view_tab'=>true,
                'use_buildin_selectrow'=>true,
                'use_buildin_filter'=>true,
                'orderBy'=> 'ar_id desc',
                'finder_cols'=>'ar_bn,channel_name,trade_time,member,type,order_bn,column_sale_money,column_fee_money,money,status,confirm_money,unconfirm_money,charge_status,charge_time,monthly_status,column_delete',
            ));
    }

    function _views(){
        $shopList = financebase_func::getShopList(financebase_func::getShopType());

        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'),'filter'=>'','addon'=>'showtab','optional'=>false),
            1 => array('label'=>app::get('base')->_('未匹配账期'),'filter'=>array(
                'monthly_item_id'=>0, 
                'channel_id' => array_column($shopList, 'shop_id')
            ),'addon'=>'showtab','optional'=>false),
        );
        return $sub_menu;
    }

    //批量记账
    /**
     * do_charge
     * @return mixed 返回值
     */
    public function do_charge(){
        $this->begin('javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();');
        $arObj = app::get('finance')->model('ar');
        if($_POST['isSelectedAll'] == '_ALL_'){
            $where = $arObj->_filter($_POST);
            $sql = 'select ar_id from sdb_finance_ar where '.$where;
            $rs = kernel::database()->select($sql);
            $ids = array();
            foreach($rs as $v){
                $ids['ar_id'][] =$v['ar_id'];
            }
        }else{
            $ids = $_POST;
        }
        $res = kernel::single('finance_ar')->do_charge($ids);
        if($res['status'] == 'fail'){
            $this->end(false,$res['msg'],'javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();');
        }
        $this->end(true,'操作成功！','javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();');
    }

    /**
     * do_cancel
     * @return mixed 返回值
     */
    public function do_cancel(){
        $id = $_GET['id'];
        $data = array('res'=>'succ','msg'=>'');
        $arObj = &app::get('finance')->model('ar');
        $rs = $arObj->delete(array('ar_id'=>$id));
        if(!$rs){
            $data = array('res'=>'fail','msg'=>'作废不成功');
            echo json_decode($data);exit;
        }
        kernel::single('finance_ar_verification')->change_verification_flag($id);
        echo json_encode($data);
    }

    public function importTemplate_act($filter = array(),$params = array()){
        $this->pagedata['checkTime'] = $params['checkTime'];
        return $this->fetch('ar/io/import_filetype.html');
    }

    /**
     * exportTemplate_act
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function exportTemplate_act($filter = ''){
        return $this->fetch('ar/export.html');
    }

    /**
     * matchReport
     * @return mixed 返回值
     */
    public function matchReport() {
        $filter = array(
            'monthly_item_id' => '0',
        );
        $filter = array_merge($filter, $_POST);
        $list = app::get('finance')->model('ar')->getList('ar_id', $filter, 0, 10000);
        $GroupList = array_column($list, 'ar_id');
        $this->pagedata['request_url'] = $this->url.'&act=doMatchReport';
        $this->pagedata['itemCount'] = count($GroupList);
        $this->pagedata['GroupList'] = json_encode($GroupList);
        $this->pagedata['maxNum']    = 100;
        parent::dialog_batch();
    }

    /**
     * doMatchReport
     * @return mixed 返回值
     */
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
            list($rs, $rsData) = kernel::single('finance_monthly_report_items')->dealArMatchReport($itemId);
        
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

    /**
     * reGenerate
     * @return mixed 返回值
     */
    public function reGenerate() {
        $filter = array(
            'status' => '0',
        );
        $filter = array_merge($filter, $_POST);
        $list = app::get('finance')->model('ar')->getList('ar_id', $filter, 0, 10000);
        $GroupList = array_column($list, 'ar_id');
        $this->pagedata['request_url'] = $this->url.'&act=doReGenerate';
        $this->pagedata['itemCount'] = count($GroupList);
        $this->pagedata['GroupList'] = json_encode($GroupList);
        $this->pagedata['maxNum']    = 10;
        parent::dialog_batch();
    }

    /**
     * doReGenerate
     * @return mixed 返回值
     */
    public function doReGenerate() {
        $itemIds = explode(',',$_POST['primary_id']);

        if (!$itemIds) { echo 'Error: 缺少单据';exit;}

        $retArr = array(
            'itotal'  => count($itemIds),
            'isucc'   => 0,
            'ifail'   => 0,
            'err_msg' => array(),
        );
        $saleModel     = app::get('ome')->model('sales');
        $mdlSalesAftersale      = app::get('sales')->model('aftersale');
        foreach($itemIds as $itemId) {
            $row = app::get('finance')->model('ar')->db_dump(['ar_id'=>$itemId]);
            if($row['status'] == '0') {
                app::get('finance')->model('ar')->delete(['ar_id'=>$itemId, 'status'=>'0']);
                if($row['ar_type'] == 1) {
                    $list   = $mdlSalesAftersale->getList('*', ['aftersale_bn'=>$row['serial_number']]);
                    $list = array_column($list, null, 'aftersale_bn');
                    kernel::single('finance_cronjob_tradeScript')->dealAftersale($list);
                } else {
                    $list   = $saleModel->getList('*', ['sale_bn'=>$row['serial_number']]);
                    kernel::single('finance_cronjob_tradeScript')->dealSales($list);
                }
                $retArr['isucc'] ++;
            } else {
                $retArr['ifail'] += 1;
                $retArr['err_msg'][] = $row['ar_bn'].':已核销不能重新生成';
            }
        }
        echo json_encode($retArr),'ok.';exit;
    }
}