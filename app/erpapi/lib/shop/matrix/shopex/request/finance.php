<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @desc
 * @author: jintao
 * @since: 2016/7/21
 */
class erpapi_shop_matrix_shopex_request_finance extends erpapi_shop_request_finance {

    /**
     * 添加Payment
     * @param mixed $payment payment
     * @return mixed 返回值
     */

    public function addPayment($payment) {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if(!$payment) {
            $rs['msg'] = 'no payment';
            return $rs;
        }
        $payment['t_begin']   = $payment['t_begin'] ? $payment['t_begin'] : time();
        $payment['t_end']     = $payment['t_end'] ? $payment['t_end'] : time();
        $payment['cur_money'] = $payment['cur_money'] ? $payment['cur_money'] : $payment['money'];
        //支付信息
        $paymentCfgModel = app::get('ome')->model('payment_cfg');
        $cfg = $paymentCfgModel->dump(array('id'=>$payment['payment']), 'pay_bn,custom_name');
        $payment['pay_bn']    = $cfg['pay_bn'];
        $payment['paymethod'] = $cfg['custom_name'];

        // 订单
        $orderModel = app::get('ome')->model('orders');
        $order = $orderModel->dump($payment['order_id'], 'order_id,order_bn,member_id,shop_id');

        // 会员信息
        $memberModel = app::get('ome')->model('members');
        $memberinfo = $memberModel->dump($order['member_id'],'uname,name,member_id');

        $params = array();
        $params['shop_id']          = $order['shop_id'];
        $params['tid']              = $order['order_bn'];
        $params['payment_id']       = $payment['payment_bn'];
        $params['buyer_id']         = $memberinfo['account']['uname'];
        $params['seller_account']   = $payment['account']?$payment['account']:'';
        $params['seller_bank']      = $payment['bank']?$payment['bank']:'';
        $params['buyer_account']    = $payment['pay_account']?$payment['pay_account']:'';
        $params['currency']         = $payment['currency']?$payment['currency']:'CNY';
        $params['pay_fee']          = $payment['money'];
        $params['paycost']          = $payment['paycost']?$payment['paycost']:'';
        $params['currency_fee']     = $payment['cur_money']?$payment['cur_money']:'';
        $params['pay_type']         = $payment['pay_type'];
        $params['payment_tid']      = $payment['pay_bn'];
        $params['payment_type']     = $payment['paymethod']?$payment['paymethod']:'';
        $params['t_begin']          = date("Y-m-d H:i:s",$payment['t_begin']);
        $params['t_end']            = date("Y-m-d H:i:s",$payment['t_end']);
        $params['memo']             = $payment['memo']?$payment['memo']:'';
        $params['status']           = strtoupper($payment['status']);
        $params['payment_operator'] = kernel::single('desktop_user')->get_login_name();
        $params['op_name'] = kernel::single('desktop_user')->get_login_name();
        $params['outer_no']         = $payment['trade_no']?$payment['trade_no']:'';#支付网关的内部交易单号
        $params['modify']           = date("Y-m-d H:i:s", time());
        $callback = array(
            'class' => get_class($this),
            'method' => 'addPaymentCallback',
            'params' => array(
                'shop_id' => $order['shop_id'],
                'tid' => $order['order_bn']
            )
        );
        $title = '店铺('.$this->__channelObj->channel['name'].')发起交易支付请求(金额:'.$params['pay_fee'].',支付方式:'.$params['payment_type'].')]订单号:'.$params['tid'];
        $rs = $this->__caller->call(SHOP_ADD_PAYMENT_RPC, $params, $callback, $title, 10, $order['order_bn']);
        return $rs;
    }

    /**
     * 添加PaymentCallback
     * @param mixed $response response
     * @param mixed $callback_params 参数
     * @return mixed 返回值
     */
    public function addPaymentCallback($response, $callback_params) {
        $status = $response['rsp'];
        $order_bn = $callback_params['tid'];
        $shop_id = $callback_params['shop_id'];

        // 订单
        $oOrder = app::get('ome')->model('orders');
        $order_detail = $oOrder->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id), 'order_id,pay_status');
        $order_id = $order_detail['order_id'];
        $data = array(
            'order_id' => $order_id,
            'type' => 'payment'
        );

        $api_failObj = app::get('ome')->model('api_fail');
        $oOperation_log = app::get('ome')->model('operation_log');
        if ($status != 'succ'){
            if ($order_detail['pay_status'] == '8'){
                //状态回滚，变成未付款/部分付款
                kernel::single('ome_order_func')->update_order_pay_status($order_id, true, __CLASS__.'::'.__FUNCTION__);
                //此订单出现在付款确认的“付款失败”标签页里,并在操作日志中记录“前端拒绝支付，付款失败”
                $api_failObj->insert($data);
            }elseif(in_array($order_detail['pay_status'],array('1','3'))){
                $api_failObj->delete($data);
            }
            //操作日志
            $oOperation_log->write_log('order_payment@ome',$order_id,'订单号:'.$order_bn.'发起支付请求,前端拒绝支付,付款失败');
        }else{
            $api_failObj->delete($data);
        }
        return $this->callback($response, $callback_params);
    }

    /**
     * 更新PaymentStatus
     * @param mixed $payment payment
     * @return mixed 返回值
     */
    public function updatePaymentStatus($payment) {
        // 订单
        $orderObj = app::get('ome')->model('orders');
        $order = $orderObj->dump($payment['order_id'], 'order_bn');
        $params['tid']         = $order['order_bn'];
        $params['payment_id '] = $payment['payment_bn'];
        $params['oid ']        = '';#子订单id
        $params['status']      = strtoupper($payment['status']);
        $callback = array(
            'class' => get_class($this),
            'method' => 'callback',
        );
        $title = '店铺('.$this->__channelObj->channel['name'].')更新[交易支付单状态'.$params['status'].'](订单号:'.$order['order_bn'].'付款单号:'.$payment['payment_bn'].')';
        $rs = $this->__caller->call(SHOP_UPDATE_PAYMENT_STATUS_RPC,$params,$callback,$title,10,$order['order_bn']);
        return $rs;
    }

    /**
     * 添加Refund
     * @param mixed $refund refund
     * @return mixed 返回值
     */
    public function addRefund($refund){
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$refund) {
            $rs['msg'] = 'no refund';
            return $rs;
        }
        
        // 如果不是售后退款,且存在取消发货单状态,如果状态为失败则不请求
        if (isset($refund['is_aftersale_refund']) && !$refund['is_aftersale_refund'] && isset($refund['cancel_dly_status']) && $refund['cancel_dly_status'] == 'FAIL') {
            $rs['msg'] = '发货单取消失败的售前退款,暂不同步';
            return $rs;
        }
        
        //支付方式信息
        $paymentCfgModel = app::get('ome')->model('payment_cfg');
        $payment_cfg = $paymentCfgModel->dump(array('id'=>$refund['payment']), 'pay_bn,custom_name');
        $refund['pay_bn'] = $payment_cfg['pay_bn'];
        $refund['paymethod'] = $payment_cfg['custom_name'];
        $params = array();
        $params = $this->_setParams($refund);

        $callback = array(
            'class' => get_class($this),
            'method' => 'addRefundCallback',
            'params' => array(
                'shop_id' => $params['shop_id'],
                'tid' => $params['tid'],
                'refund_apply_id' => $params['refund_apply_id']
            )
        );

        $title = '店铺('.$this->__channelObj->channel['name'].')添加[交易退款单(金额:'.$params['refund_fee'].')](订单号:'.$params['tid'].'退款单号:'.$params['refund_id'].')';
        $rs = $this->__caller->call(SHOP_ADD_REFUND_RPC,$params,$callback,$title,10,$params['tid']);
        return $rs;
    }

    protected function _setParams($refund){
        // 订单信息
        $orderModel = app::get('ome')->model('orders');
        if ($refund['is_archive'] == '1') {
            $orderModel = app::get('archive')->model('orders');
        }
        $order = $orderModel->dump($refund['order_id'], 'order_bn,member_id,shop_id');
        // 会员
        $memberModel = app::get('ome')->model('members');
        $member = $memberModel->dump(array('member_id'=>$order['member_id']),'uname,name,member_id');
        $params = array();
        $params['shop_id']         = $order['shop_id'];
        $params['tid']             = $order['order_bn'];
        $params['refund_id']       = $refund['refund_bn'];
        $params['refund_apply_id'] = $refund['apply_id'];
        $params['buyer_account']   = $refund['account']?$refund['account']:'';
        $params['buyer_bank']      = $refund['bank']?$refund['bank']:'';
        $params['seller_account']  = $refund['pay_account']?$refund['pay_account']:'';
        $params['buyer_name']      = $member['contact']['name'];#买家姓名
        $params['buyer_id']        = $member['account']['uname'];#买家会员帐号
        $params['currency']        = $refund['currency']?$refund['currency']:'CNY';
        $params['refund_fee']      = $refund['money'];
        $params['paycost']         = $refund['paycost']?$refund['paycost']:'';
        $params['currency_fee']    = $refund['cur_money'] ? $refund['cur_money'] : $refund['money'];
        $params['pay_type']        = $refund['pay_type'];
        $params['payment_tid']     = $refund['pay_bn'];
        $params['payment_type']    = $refund['paymethod']?$refund['paymethod']:'';
        $params['t_begin']         = $refund['t_ready'] ? date("Y-m-d H:i:s",$refund['t_ready']) : date("Y-m-d H:i:s");
        $params['t_sent']          = $refund['t_sent'] ? date("Y-m-d H:i:s",$refund['t_sent']) : '';
        $params['t_received']      = $refund['t_received'] ? date("Y-m-d H:i:s",$refund['t_received']) : date("Y-m-d H:i:s");
        $params['status']          = strtoupper($refund['status']);
        $params['memo']            = $refund['memo']?$refund['memo']:'';
        $params['outer_no']        = $refund['trade_no']?$refund['trade_no']:'';
        $params['modify']          = date("Y-m-d H:i:s");

        return $params;
    }

    /**
     * 添加RefundCallback
     * @param mixed $response response
     * @param mixed $callback_params 参数
     * @return mixed 返回值
     */
    public function addRefundCallback($response, $callback_params)
    {
        $status = $response['rsp'];
        if ($status != 'succ'){
            $shop_id = $callback_params['shop_id'];
            $order_bn = $callback_params['tid'];
            $refund_apply_id = $callback_params['refund_apply_id'];
            $oOrder = app::get('ome')->model('orders');
            $order_detail = $oOrder->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id), 'order_id,pay_status');
            if (!$order_detail) {
                $oOrder = app::get('archive')->model('orders');
                $order_detail = $oOrder->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id), 'order_id,pay_status');
            }
            $order_id = $order_detail['order_id'];
            //状态回滚，变成已支付/部分付款/部分退款
            kernel::single('ome_order_func')->update_order_pay_status($order_id, true, __CLASS__.'::'.__FUNCTION__);
            #bugfix:解决如果退款单请求先到并生成单据于此同时由于网络超时造成退款申请失败，从而造成退款申请单状态错误问题。
            $refund_applyObj = app::get('ome')->model('refund_apply');
            $refundapply_detail = $refund_applyObj->getList('refund_apply_bn',array('apply_id'=>$refund_apply_id));
            $refundsObj = app::get('ome')->model('refunds');
            $refunds_detail = $refundsObj->getList('refund_id',array('refund_bn'=>$refundapply_detail[0]['refund_apply_bn'],'status'=>'succ'));
            if(!$refunds_detail){
                $refund_applyObj->update(array('status'=>'6'), array('status|notin'=>array('4'),'apply_id'=>$refund_apply_id));
                //操作日志
                $oOperation_log = app::get('ome')->model('operation_log');
                $oOperation_log->write_log('order_refund@ome',$order_id,'订单:'.$order_bn.'发起退款请求,前端拒绝退款，退款失败');
            }
        }
        return $this->callback($response, $callback_params);
    }

    /**
     * 更新RefundStatus
     * @param mixed $refundinfo refundinfo
     * @return mixed 返回值
     */
    public function updateRefundStatus($refundinfo) {
        $orderModel = app::get('ome')->model('orders');
        $order = $orderModel->dump($refundinfo['order_id'], 'order_bn');
        $params['tid']        = $order['order_bn'];
        $params['refund_id '] = $refundinfo['refund_bn'];
        $params['oid ']       = '';#子订单id
        $params['status']     = strtoupper($refundinfo['status']);
        $callback = array(
            'class' => get_class($this),
            'method' => 'callback',
        );
        $title = '店铺('.$this->__channelObj->channel['name'].')更新[交易退款状态]:'.$params['status'].'(订单号:'.$order['order_bn'].'退款单号:'.$refundinfo['refund_bn'].')';
        $rs = $this->__caller->call(SHOP_UPDATE_REFUND_STATUS_RPC,$params,$callback,$title,10,$order['order_bn']);
        return $rs;
    }
}
