<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_ctl_bill_order extends desktop_controller{
    var $name = "订单账单";
    /**
     * index
     * @return mixed 返回值
     */
    public function index(){
        /*
       if(empty($_POST)){
            $_POST['time_from'] = date("Y-n-d");
            $_POST['time_to'] = date("Y-n-d");
        }else{
            $_POST['time_from'] = $_POST['time_from'];
            $_POST['time_to'] = $_POST['time_to'];
        }
        */
        kernel::single('finance_bill_order')->set_params($_POST)->display();
    }

    /**
     * do_cancel
     * @return mixed 返回值
     */
    public function do_cancel(){
        $id = $_GET['id'];
        $data = array('res'=>'succ','msg'=>'');
        $billObj = &app::get('finance')->model('bill');
        $rs = $billObj->delete(array('bill_id'=>$id));
        if(!$rs){
            $data = array('res'=>'fail','msg'=>'作废不成功');
            echo json_decode($data);exit;
        }
        echo json_encode($data);
    }
}