<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/*
 * 考拉售后扩展
 * sunjing@shopex.cn
 */
class ome_aftersale_request_kaola extends ome_aftersale_abstract{

    //售后申请拒绝时弹出的页面. 放出的话 要在 参考 加表 class ome_ctl_admin_return extends desktop_controller {  function refuse_message($return_id,$shop_type)
    function return_button($return_id,$status){
        $rs = array('rsp'=>'default','msg'=>'','data'=>'');
        if ($status == '5') {
            $rs = array('rsp'=>'show','msg'=>'','data'=>'index.php?app=ome&ctl=admin_return&act=refuse_message&p[0]='.$return_id.'&p[1]=kaola');
        }
        return $rs;
    }
 
    //保存退款时按钮直接跳转还是dialog
    function refund_button($apply_id,$status){
        $rs = array('rsp'=>'default','msg'=>'成功','data'=>'');
        if ($status == '3'){
            $rs = array('rsp'=>'show','msg'=>'','data'=>'index.php?app=ome&ctl=admin_refund_apply&act=upload_refuse_message&p[0]='.$apply_id.'&p[1]=kaola');
        }
        return $rs;
    }
    
    //退款状态保存前扩展
    function pre_save_refund($apply_id,$data){
        set_time_limit(0);
        $oRefund_apply = app::get('ome')->model('refund_apply');
        $refunddata = $oRefund_apply->refund_apply_detail($apply_id);
        if ($data['status'] == '3' || $data['status'] == '2') {
            $result = kernel::single('ome_service_refund_apply')->update_status($refunddata,$data['status'],'sync');
            return $result;
        }
    }

    /**
     * 售后保存前的扩展
     * @param
     * @return
     * @access  public
     * @author
     */
    function pre_save_return($data)
    {
        set_time_limit(0);
        $rs = array('rsp'=>'succ','msg'=>'','data'=>'');
        $return_id = $data['return_id'];
        $status = $data['status'];
        if ($status == '3' || $status == '5') {
            $rsp = kernel::single('ome_service_aftersale')->update_status($return_id, $status,'sync');
            if ($rsp  && $rsp['rsp'] == 'fail') {
                $rs['rsp'] = 'fail';
                $rs['msg'] = $rsp['msg'];
            }
        }

        return $rs;
    }
}
?>