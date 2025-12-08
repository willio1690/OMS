<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @desc 退款数据处理
 * @author: jintao
 * @since: 2016/7/21
 */
class erpapi_shop_response_refund extends erpapi_shop_response_abstract {

    protected function _formatAddParams($params) {
        $version = $this->__channelObj->get_ver();
        $t_ready = $params['t_ready'] ? $params['t_ready'] : $params['t_sent'];
        $t_ready = kernel::single('ome_func')->date2time($t_ready);

        $t_sent  = $params['t_sent'] ? $params['t_sent'] : $params['t_ready'];
        $t_sent  = kernel::single('ome_func')->date2time($t_sent);

        $sdf = array(
            'refund_bn' => $params['refund_bn'],
            'order_bn' => $params['order_bn'],
            'status' => $params['status'],
            'refund_type' => $params['refund_type'],
            'money' => sprintf('%.2f', $params['money']),
            'cod_zero_accept' => false, //货到付款0元退款单是否接受
            'memo' => $params['memo'],
            'account' => $params['account'],
            'bank' => $params['bank'],
            'pay_account' => $params['pay_account'],
            'paycost' => $params['paycost'],
            'cur_money' => $params['cur_money'] ? $params['cur_money'] : $params['money'],
            'pay_type' => $params['pay_type'] ? $params['pay_type'] : 'online',
            'payment' => $params['payment'],
            'paymethod' => $params['paymethod'],
            'trade_no' => $params['trade_no'],
            'oid' => $params['oid'],
            't_ready' => $t_ready ? $t_ready : time(),
            't_sent' => $t_sent ? $t_sent : time(),
            't_received' => kernel::single('ome_func')->date2time($params['t_received']),
            'update_order_payed' => $version == '1' ? true : false, //是否更新订单金额
            'version' => $version,
            'refund_version_change' => false
        );
        return $sdf;
    }

    /**
     * 添加
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function add($params) {
        $this->__apilog['title'] = '店铺(' . $this->__channelObj->channel['name'] . ')退款业务处理[退款单：' . $params['refund_bn'] . ']';
        $this->__apilog['original_bn'] = $params['order_bn'];
        $this->__apilog['result']['data'] = array('tid'=>$params['order_bn'],'refund_id'=>$params['refund_bn'],'retry'=>'false');
        $sdf = $this->_formatAddParams($params);
        if(empty($sdf)) {
            if(!$this->__apilog['result']['msg']){
                $this->__apilog['result']['msg'] = '退款单不走此接口';
            }
            return false;
        }
        $shopId = $sdf['shop_id'] = $this->__channelObj->channel['shop_id'];
        $sdf['shop_type'] = $this->__channelObj->channel['shop_type'];
        $field = 'pay_status,status,process_status,order_id,payed,cost_payment,ship_status,is_cod';
        $tgOrder = $this->getOrder($field, $shopId, $sdf['order_bn']);
        if (empty($tgOrder)) {
            
            $this->__apilog['result']['msg'] = '缺少订单' . $sdf['order_bn'];
            return false;
        }
        $sdf['order'] = $tgOrder;
        // memo里是否带了退款申请单
        $refund_apply_bn = $sdf['refund_bn'];
        if($sdf['memo']) {
            if (preg_match('/#(\d+)#/', $sdf['memo'], $matches)) {
                $refund_apply_bn = $matches[1];
            }
            $sdf['memo'] = preg_replace('/#(\d+)#/', '', $sdf['memo']);
        }
        // 退款申请单
        $refundApplyModel = app::get('ome')->model('refund_apply');
        $refundApply = $refundApplyModel->getList('apply_id,return_id,refund_apply_bn,refund_refer,status,money,payment,memo,addon', array('refund_apply_bn'=>$refund_apply_bn,'shop_id'=>$shopId), 0, 1);
        if($refundApply) {
            $sdf['refund_apply'] = $refundApply[0];
        }
        // 退款单
        $refundModel = app::get('ome')->model('refunds');
        $refund = $refundModel->getList('refund_id', array('refund_bn'=>$sdf['refund_bn'],'shop_id'=>$shopId));
        if($refund) {
            $sdf['refund'] = $refund[0];
        }
        $pay_bn = $sdf['payment'];
        if ($pay_bn) {
            $payment_cfg = $this->get_payment($pay_bn,$this->__channelObj->channel['shop_type']);
            $sdf['payment'] = $payment_cfg['id'];
        }
        return $sdf;
    }

    protected function _formatStatusUpdateParams($params) {
        $version = $this->__channelObj->get_ver();
        $sdf = array(
            'order_bn' => $params['order_bn'],
            'refund_bn' => $params['refund_bn'],
            'status' => $params['status'],
            'update_order_payed' => $version == '1' ? true : false, //是否更新订单金额
        );
        return $sdf;
    }

    /**
     * statusUpdate
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function statusUpdate($params) {
        $this->__apilog['title'] = '店铺(' . $this->__channelObj->channel['name'] . ')更新退款单状态[退款单：' . $params['refund_bn'] . ']';
        $this->__apilog['original_bn'] = $params['order_bn'];
        $this->__apilog['result']['data'] = array('tid'=>$params['order_bn'],'refund_id'=>$params['refund_bn'],'retry'=>'false');
        $sdf = $this->_formatStatusUpdateParams($params);
        if(empty($sdf)) {
            if(!$this->__apilog['result']['msg']){
                $this->__apilog['result']['msg'] = '退款单更新状态不走此接口';
            }
            return false;
        }
        $shopId = $this->__channelObj->channel['shop_id'];
        // 订单
        $orderModel = app::get('ome')->model('orders');
        $order = $orderModel->getList('order_id', array('order_bn' => $sdf['order_bn'],'shop_id' => $shopId), 0, 1);
        if (!$order) {
            $this->__apilog['result']['msg'] = '缺少订单'. $sdf['order_bn'];
            return false;
        }
        $sdf['order'] = $order[0];
        // 退款单
        $refundModel = app::get('ome')->model('refunds');
        $refund = $refundModel->getList('refund_id,money,status', array('refund_bn'=>$sdf['refund_bn'],'shop_id'=>$shopId), 0, 1);
        if (!$refund) {
            $this->__apilog['result']['msg'] = '没有退款单' . $sdf['refund_bn'];
            return false;
        }
        $sdf['refund'] = $refund[0];
        return $sdf;
    }
}