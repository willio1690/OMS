<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2018/04/24
 * @describe 财务处理
 */
class erpapi_shop_matrix_congminggou_request_finance extends erpapi_shop_request_finance
{
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
        $title = '店铺('.$this->__channelObj->channel['name'].')添加退款单(订单号:'.$refund['order_bn'].'退款单号:'.$refund['refund_bn'].')';
        $addon = unserialize($refund['addon']);
        $params = array(
            'state' => $addon['reship_id'] > 0 ? '70' : '30',  # 订单状态（30：无人签收退货、70：签收退货）
            'tid' => $refund['order_bn'],  # 订单号
            'remark' => kernel::single('desktop_user')->get_name() . '操作,' . $refund['memo'],  # 退款说明
            'terminalid' => $this->__channelObj->channel['addon']['terminal'],  # 商户终端号
            'refundvalue' => $refund['money'],  # 退款金额

        );
        $callback = array(
            'class' => get_class($this),
            'method' => 'addRefundCallback',
            'params' => array(
                'shop_id' => $refund['shop_id'],
                'tid' => $refund['order_bn'],
                'refund_apply_id' => $refund['apply_id']
            )
        );

        $rs = $this->__caller->call(SHOP_ADD_REFUND_RPC,$params,$callback,$title,10,$refund['order_bn']);
        return $rs;
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
        $data = @json_decode($response['data'], 1);
        $refund_apply_id = $callback_params['refund_apply_id'];
        if ($status != 'succ' && !in_array($data['data']['returncode'], array('0010', '0011'))){
            $shop_id = $callback_params['shop_id'];
            $order_bn = $callback_params['tid'];
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
        } else {
            $objRefund = kernel::single('ome_refund_apply');
            $objRefund->refund_apply_accept($refund_apply_id, array('call_from'=>'erpapi'));
        }
        return $this->callback($response, $callback_params);
    }
}
