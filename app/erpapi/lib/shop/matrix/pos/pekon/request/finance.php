<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_pos_pekon_request_finance extends erpapi_shop_request_finance {

    
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
       
        $params = array();
        $params = $this->_setParams($refund);

        $callback = array(
            
        );

        $title = '店铺('.$this->__channelObj->channel['name'].')添加[交易退款单(金额:'.$refund['money'].')](订单号:'.$refund['order_bn'].'退款单号:'.$refund['refund_bn'].')';
        $rs = $this->__caller->call('CreateRefundOrder',$params,$callback,$title,10,$refund['order_bn']);

        return $rs;
    }

    protected function _setParams($refund){
        // 订单信息
        $orderModel = app::get('ome')->model('orders');
     
        $order = $orderModel->dump($refund['order_id'], 'order_bn,member_id,shop_id,pay_bn,paytime,total_amount');
        $applyMdl = app::get('ome')->model('refund_apply');
        $applys = $applyMdl->db_dump(array('apply_id'=>$refund['apply_id']),'product_data,cost_freight');
        
        $product_data = $applys['product_data'] ? unserialize($applys['product_data']) : [];
        $orderItems = [];
        $totalQuantity = 0;
        foreach ((array) $product_data as $k => $v) {

            $oid = $v['oid'];
            $oid = $oid ? list($itemoid,$seqno)=explode('_',$oid) : '';
            $amount = $v['num']*$v['price'];
            $totalQuantity+=(int) $v['num'];
       
            $orderItems[] = array(

                'itemSeqNo'             =>  ($k + 1),
                'refSalesOrderItemSeqNo'=>  $seqno,
                'productSkuCode'        =>  $v['bn'],
                'price'                 =>  $v['price'] ? (float) $v['price'] : 0,
                'quantity'              =>  (int) $v['num'],
                'amount'                =>  $amount,
            );
        }
        $payments = [];
        if(kernel::single('ome_return')->checkMixPay($refund['order_id'])){

            $total_amount = kernel::single('eccommon_math')->number_minus(array($order['total_amount'],$refund['money']));

            if($total_amount == 0){//说明是全退
            //
                $paymentsMdl = app::get('ome')->model('payments');
                $payment_list = $paymentsMdl->getlist('*',array('order_id'=>$order_id));
                foreach($payment_list as $v){
                    $payments[] =[
                        'pay_bn'    =>  $v['pay_bn'],
                        'paytime'   =>  date('Y-m-d H:i:s'),
                        'totalmoney'=>  $v['money'],
                        
                    ];
                }
            }else{
                $payments[] =[
                    'pay_bn'    =>  $order['pay_bn'],
                    'paytime'   =>  date('Y-m-d H:i:s'),
                    'totalmoney'=>  $refund['money'],
                   
                ];
            }
        }else{
            
            $payments[]=[
                'payType'       =>  $order['pay_bn'],
                'amount'        =>  $refund['money'],
                'payTime'       =>  date('Y-m-d H:i:s'),


            ];
            
        }

        $params = array(
            'actualOrderSource' =>  'OMS',
            'orderType'         =>  'YDR',
            'returnType'        =>  'PART',
            'refundType'        =>  'OnlyRefund',
            'thirdpartyOrderNo' =>  $refund['refund_bn'],
            'referenceOrderNo'  =>  $order['order_bn'],
            'businessTime'      =>  $refund['t_ready'] ? date("Y-m-d H:i:s",$refund['t_ready']) : date("Y-m-d H:i:s"),
            'salesOrgCode'      =>  $sdf['shop_bn'],
            'totalQuantity'     =>  $totalQuantity,

            'amount'            =>  $refund['money'],
            'currencyCode'      =>  'CNY',
            'freight'           =>  $applys['cost_freight'] ? $applys['cost_freight'] : 0,
            'refundReasonText'  =>  '售前退款',
            'auditStatus'       =>  'AUDIT_SUCCESS',

            'orderItems'        =>  $orderItems,
            'payments'          =>  $payments,

        );

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

    
}
