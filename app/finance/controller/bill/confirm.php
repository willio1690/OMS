<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_ctl_bill_confirm extends desktop_controller{

    var $name = "无归属账单";

    /**
     * index
     * @return mixed 返回值
     */
    public function index(){
        
        $this->finder('finance_mdl_bill_confirm',array(
            'title'=>app::get('finance')->_('无归属账单'),
            'actions'=>array(
                array(
                    'label' => '批量作废',
                    'submit' => 'index.php?app=finance&ctl=bill_confirm&act=do_cancel&finder_id='.$_GET['finder_id'],
                ),
            ),
            'use_buildin_recycle'=>false,
            'use_view_tab'=>false,
            'use_buildin_selectrow'=>true,
            'use_buildin_filter'=>true,
        ));
    }

    /**
     * 记账
     * @param finder操作按钮
     */
    function confirm($confirm_id=''){
        if($confirm_id){
            $detail = kernel::single('finance_billconfirm')->dump($confirm_id);
        }

        $this->pagedata['fee_type_data'] = kernel::single('finance_bill')->get_fee_type_item_relation();
        $this->pagedata['json'] = json_encode($this->pagedata['fee_type_data']);
        $this->pagedata['detail'] = $detail;
        $this->display("bill/confirm.html");
    }

    /**
     * 记账处理
     * @param finder操作按钮
     */
    function do_confirm(){
        $data = $_POST;
        $confirm_id = intval($data['confirm_id']);
        if($confirm_id){
            $detail = kernel::single('finance_billconfirm')->dump($confirm_id);
        }
        
        $this->begin('javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();');
        $autohide = array("autohide"=>2000);

        $order_bn = trim($data['order_bn']);
        if ( empty($order_bn) ){
            $this->end(false, app::get('finance')->_('订单号不能为空'));
        }else{
            $funcObj = kernel::single('finance_func');
            if ( !$funcObj->order_is_exists($order_bn) ){
                $err = array_merge($autohide,array('err_msg'=>'订单号不存在'));
                $this->end(false, app::get('finance')->_('订单号不存在'), '', $err);
            }
        }
        $fee_obj = $data['fee_obj'];
        $fee_item = trim($data['fee_item']);
        if ( empty($fee_item) ){
            $this->end(false, app::get('finance')->_('请选择费用项'));
        }

        $money = abs($detail['money']);
        $bill_sdf = array(
            'order_bn' => $order_bn,
            'money' => $detail['in_out_type'] == 'out' ? -$money : $money,
            'fee_obj' => $fee_obj,
            'fee_item' => $fee_item,
            'trade_time' => $detail['trade_time'],
            'member' => $detail['trade_account'],
            'channel_id' => $detail['channel_id'],
            'channel_name' => $detail['channel_name'],
            'credential_number' => $detail['trade_no'],
            'charge_status' => '0',//记账状态:未记账
            'memo' => $detail['order_title'],
            'unique_id' => md5($detail['trade_no'].$detail['trade_time'].'-'.$money.'-'.$detail['balance'])
        );
        $rs = kernel::single('finance_bill')->do_save($bill_sdf);
        if ( $rs['status'] == 'success' ){
            #删除无归属账单
            kernel::single('finance_billconfirm')->delete($confirm_id);
            $this->end(true, app::get('finance')->_('操作成功'));
        }else{
            $this->end(false, app::get('finance')->_('操作失败:'.$rs['msg']));
        }
    }

    /**
     * 判断订单号是否存在
     * @access public
     * @param String $order_bn 订单号
     * @return json
     */
    public function order_is_exists($order_bn=''){
        $rs = array('rsp'=>'fail','msg'=>'订单号不存在');
        if (empty($order_bn)) return $rs;

        $funcObj = kernel::single('finance_func');
        if($funcObj->order_is_exists($order_bn)){
            $rs['rsp'] = 'succ';
        }      

        echo json_encode($rs);
        exit;
    }

    /**
     * 作废
     * @access public
     * @param Int $confirm_id 账单ID
     * @return bool
     */
    public function do_cancel($confirm_id=''){
        $this->begin('javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();');
        
        if ($confirm_id){
            $filter = $confirm_id;
        }elseif(isset($_POST['confirm_id'])){
            $filter = $_POST['confirm_id'];
        }else{
            $filter = $_POST;
        }
        $rs = kernel::single('finance_billconfirm')->cancel($filter);

        $this->end($rs,$rs ? '作废成功' : '作废失败','javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();');
    }

}