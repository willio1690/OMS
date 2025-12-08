<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_ctl_ar_verification extends desktop_controller{
	var $name = "应收对冲";
    /**
     * index
     * @return mixed 返回值
     */
    public function index(){
       if(empty($_POST)){
            $_POST['time_from'] = date("Y-n-d");
            $_POST['time_to'] = date("Y-n-d");
        }else{
            $_POST['time_from'] = $_POST['time_from'];
            $_POST['time_to'] = $_POST['time_to'];
        }
        kernel::single('finance_ar_verification')->set_params($_POST)->display();
    }

    /**
     * verificate
     * @return mixed 返回值
     */
    public function verificate(){
        $order_bn = $_GET['order_bn'];
        $data = kernel::single('finance_ar_verification')->get_ar_by_order_bn($order_bn);
        $time_from = $_GET['time_from'];
        $time_to = $_GET['time_to'];
        $next_order_bn = kernel::single('finance_ar_verification')->get_next_order_bn($order_bn,$time_from,$time_to);
        $this->pagedata['minus'] = $data['minus'];
        $this->pagedata['plus'] = $data['plus'];
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->pagedata['time_from'] = $time_from;
        $this->pagedata['time_to'] = $time_to;
        $this->pagedata['next_order_bn'] = $next_order_bn['order_bn'];
        if(isset($_GET['flag']) && $_GET['flag'] == 'replace'){
            $this->pagedata['replace'] = true;
        }else{
            $this->pagedata['replace'] = false;
        }
        $html = $this->fetch('ar/verification.html');
        echo $html;
    }

    //应收互冲操作
    /**
     * do_verificate
     * @return mixed 返回值
     */
    public function do_verificate(){
        $this->begin('javascript:finderGroup["'.$_POST['finder_id'].'"].refresh();');
        if(empty($_POST['minus']) || empty($_POST['plus'])){
            $this->end(false,'正负应收单据不能为空');
        }
        $rs = kernel::single('finance_ar_verification')->do_verificate($_POST['plus'],$_POST['minus'],$_POST['trade_time']);
        if($rs['status'] == 'fail'){
            $this->end(false,$rs['msg']);
        }
        $this->end(true,'操作成功');
    }

    //异步判断核销金额的大小
    /**
     * sync_do_verificate
     * @return mixed 返回值
     */
    public function sync_do_verificate(){
        if(empty($_POST['minus']) || empty($_POST['plus'])){
            $res = array('status'=>'fail','msg'=>'正负应收单据不能为空');
            echo json_encode($res);exit;
        }
        $res = kernel::single('finance_ar_verification')->do_verificate($_POST['plus'],$_POST['minus'],$_POST['trade_time'],1);
        if($res['status'] == 'success'){
            switch ($res['msg_code']) {
                case '1':
                    $res['msg'] = '全额对冲，是否确认？';
                    break;
                
                case '2':
                    $res['msg'] = '未核销正应收合计小于负应收合计，将按未核销金额由低到高的顺序核销，是否确认？';
                    break;
                case '3':
                    $res['msg'] = '未核销正应收合计大于负应收合计，将按未核销金额由低到高的顺序核销，是否确认？';
                    break;
            }
        }
        echo json_encode($res);
    }

    function findplus(){
        $filter = array('charge_status'=>1,'status|noequal'=>2,'money|than'=>0);
        $params = array(
            'title'=>'正应收收单据',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_view_tab' => false,
            'use_buildin_filter'=>true,
            'base_filter' => $filter,
        );
        $this->finder('finance_mdl_ar', $params);
    }

    function findminus(){
        $filter = array('charge_status'=>1,'status|noequal'=>2,'money|lthan'=>0);
        $params = array(
            'title'=>'负应收收单据',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_view_tab' => false,
            'use_buildin_filter'=>true,
            'base_filter' => $filter,
        );
        $this->finder('finance_mdl_ar', $params);
    }

    /**
     * 获取data
     * @return mixed 返回结果
     */
    public function getdata(){
        $arObj = &app::get('finance')->model('ar');
        $ar_id = $_POST['ar_id'];
        $data = $arObj->getList('*',array('ar_id'=>$ar_id));
        foreach($data as $k=>$v){
            $data[$k] = $v;
            $data[$k]['trade_time'] = date('Y-m-d',$v['trade_time']);
            $data[$k]['type'] = kernel::single('finance_ar')->get_name_by_type($v['type']);
        }
        echo json_encode($data);
    }

    /**
     * sync_do_charge
     * @return mixed 返回值
     */
    public function sync_do_charge(){
        $res = array('status'=>'succ','msg'=>'');
        $ar_id = $_POST['ar_id'];
        $id = array('ar_id'=>array('0'=>$ar_id));
        $res = kernel::single('finance_ar')->do_charge($id);
        if($res['status'] == 'fail') {
            $res = array('status'=>'fail','msg'=>'记账失败');
            echo json_encode($res);
            exit;
        }
        $arObj = &app::get('finance')->model('ar');
        $cols = 'ar_id,ar_bn,member,order_bn,trade_time,serial_number,channel_name,type,money,unconfirm_money,confirm_money,charge_status';
        $data = $arObj->getList($cols,array('ar_id'=>$ar_id));
        foreach($data as $k=>$v){
            $data[$k]['type'] = kernel::single('finance_ar')->get_name_by_type($v['type']);
        }
        $res['msg'] = $data[0];
        echo json_encode($res);
    }

    function sync_do_cancel(){
        $id = $_POST['id'];
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
}