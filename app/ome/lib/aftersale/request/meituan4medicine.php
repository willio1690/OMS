<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_aftersale_request_meituan4medicine  extends ome_aftersale_abstract{

    function show_aftersale_html(){

        $html = '';
        return $html;
    }
//
//    function return_api(){
//        return true;
//    }
    /**
     * 退款状态保存前扩展
     * @param   data msg
     * @return
     * @access  public
     * @author
     */
    function pre_save_refund($apply_id,$data)
    {
        set_time_limit(0);

        $oRefund_apply = app::get('ome')->model('refund_apply');
        $refunddata = $oRefund_apply->refund_apply_detail($apply_id);
        if ($data['status'] == '2' || $data['status'] == '3') {
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
    
    /**
     * 获取AftersaleReason
     * @param mixed $type type
     * @return mixed 返回结果
     */
    public function getAftersaleReason($type = ''){
        $reason = [
            //“退货退款”驳回退货的原因
            'return_reship' => array(
                ['reason_id' => 1, 'reason_text' => '已和用户沟通一致不退货'],
                ['reason_id' => 2, 'reason_text' => '商品发出时完好'],
                ['reason_id' => 3, 'reason_text' => '商品没有问题，买家未举证'],
                ['reason_id' => 4, 'reason_text' => '商品没有问题，买家举证无效'],
                ['reason_id' => 5, 'reason_text' => '商品已经影响二次销售'],
                ['reason_id' => 6, 'reason_text' => '申请时间已经超过售后服务时限'],
                ['reason_id' => 7, 'reason_text' => '不支持买家主观原因的退换货'],
            ),
            //“退货退款”驳回退款的原因
            'return_refund' => array(
                ['reason_id' => 8, 'reason_text' => '已和用户沟通一致不退款'],
                ['reason_id' => 9, 'reason_text' => '收到货物有破损'],
                ['reason_id' => 10, 'reason_text' => '未收到货物'],
                ['reason_id' => 11, 'reason_text' => '买家未按要求寄出货物'],
            ),
            //“仅退款”驳回退款的原因
            'refund'        => array(
                ['reason_id' => 12, 'reason_text' => '买家未按要求寄出货物'],
                ['reason_id' => 13, 'reason_text' => '商品已开始制作'],
                ['reason_id' => 14, 'reason_text' => '商品已经打包完成'],
                ['reason_id' => 15, 'reason_text' => '商品正在配送中'],
                ['reason_id' => 16, 'reason_text' => '商品无质量问题'],
                ['reason_id' => 17, 'reason_text' => '商品没有缺货少货问题'],
                ['reason_id' => 18, 'reason_text' => '商品打包完好'],
            ),
            //驳回原因编码通用
            'other'         => array(
                ['reason_id' => 1, 'reason_text' => '其他'],
            )
        ];
        return $type ? $reason[$type] : $reason;
    }
    
    /**
     * 退款拒绝时弹出的页面
     * @param $apply_id
     * @param $status
     * @return string[]
     */
    function refund_button($apply_id,$status)
    {
        $rs = array('rsp'=>'default','msg'=>'成功','data'=>'');
        if ($status == '3') {
            $rs = array('rsp'=>'show','msg'=>'','data'=>'index.php?app=ome&ctl=admin_refund_apply&act=upload_refuse_message&p[0]='.$apply_id.'&p[1]=meituan4medicine');
        }
        return $rs;
    }
    /**
     * 售后拒绝时弹出的页面.
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function return_button($return_id,$status){
        $rs = array('rsp'=>'default','msg'=>'','data'=>'');
        if ($status == '5') {
            $rs = array('rsp'=>'show','msg'=>'','data'=>'index.php?app=ome&ctl=admin_return&act=refuse_message&p[0]='.$return_id.'&p[1]=meituan4medicine');
        }
        return $rs;
    }
}