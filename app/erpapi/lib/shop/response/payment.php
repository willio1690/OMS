<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2016/5/24
 * @describe 支付单数据处理类
 */
class erpapi_shop_response_payment extends erpapi_shop_response_abstract {

    /**
     * @param $params array
     * @return array (
     *              'order_bn' => '', #订单单号（必需）
     *              'payment_bn' => '', #支付单单号（必需）
     *              'money' => '', #支付金额（必需）
     *              'cur_money' => '', #支付金额（必需）
     *              'status' => '', #支付状态（必需）
     *              'payment' => '', #sdb_ome_payment_cfg::pay_bn
     *              'account' => '',
     *              'bank' => '',
     *              'pay_account' => '',
     *              'currency' => '',
     *              'paycost' => '',
     *              'pay_type' => '',
     *              'paymethod' => '',
     *              'memo' => '',
     *              'trade_no' => '',
     *          )
     */

    protected function _formatAddParams($params) {
        return $params;
    }

    /**
     * 添加
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function add($params) {
        $sdf = $this->_formatAddParams($params);
        $this->__apilog['title'] = '前端店铺支付业务处理[订单：' . $sdf['order_bn'].']';
        $this->__apilog['original_bn'] = $sdf['order_bn'];
        $this->__apilog['result']['data'] = array('tid'=>$sdf['order_bn'],'payment_id'=>$sdf['payment_bn'],'retry'=>'false');
        if($this->__channelObj->get_ver() == '2') {
            $this->__apilog['result']['msg'] = '版本二不做支付单添加处理';
            return false;
        }
        $shop_id    = $this->__channelObj->channel['shop_id'];
        $sdf['shop_id'] = $shop_id;
        $payment_bn = $sdf['payment_bn'];
        $order_bn   = $sdf['order_bn'];
        $paymentModel = app::get('ome')->model('payments');
        $tgPayments = $paymentModel->getList('payment_id', array('payment_bn'=>$payment_bn,'shop_id'=>$shop_id), 0, 1);
        if($tgPayments[0]['payment_id']) {
            $this->__apilog['result']['msg'] = '支付单已存在payment_id：' . $tgPayments[0]['payment_id'] . '，payment_bn：' . $payment_bn;
            return false;
        }
        $orderModel = app::get('ome')->model('orders');
        $tgOrder = $orderModel->getList('order_id, payed, total_amount, pay_status, status, process_status', array('order_bn'=>$order_bn,'shop_id'=>$shop_id), 0, 1);
        if(empty($tgOrder[0])) {
            $this->__apilog['result']['msg'] = '订单' . $order_bn . '不存在';
            return false;
        }
        $sdf['order_info'] = $tgOrder[0];
        $filter = array('order_id'=>$sdf['order_info']['order_id']);
        $otherPayments = $paymentModel->getList('payment_id, cur_money',$filter);
        $sdf['other_payment'] = $otherPayments;
        $sdf['pay_bn'] = $sdf['payment'];
        if ($sdf['pay_bn']) {
            $payment_cfg = $this->get_payment($sdf['pay_bn'],$shop_id);
            $sdf['payment'] = $payment_cfg['id'];
        }
        return $sdf;
    }

    /**
     * @param $params array
     * @return array(
     *              'order_bn' => '',
     *              'payment_bn' => '',
     *              'status' => '',
     *          )
     */
    protected function _formatStatusUpdateParams($params) {
        return $params;
    }

    public function statusUpdate($params) {
        $sdf = $this->_formatStatusUpdateParams($params);
        $this->__apilog['title'] = '更新支付单业务[订单：' . $sdf['order_bn'].']';
        $this->__apilog['original_bn'] = $sdf['order_bn'];
        $this->__apilog['result']['data'] = array('tid'=>$sdf['order_bn'],'payment_id'=>$sdf['payment_bn'],'retry'=>'false');
        if($this->__channelObj->get_ver() == '2') {
            $this->__apilog['result']['msg'] = '版本二不做支付单状态变更处理';
            return false;
        }
        $orderModel = app::get('ome')->model('orders');
        $orderFilter = array(
            'order_bn' => $sdf['order_bn'],
            'shop_id' => $this->__channelObj->channel['shop_id']
        );
        $tgOrder = $orderModel->getList('order_id,total_amount,payed', $orderFilter);
        if(empty($tgOrder)) {
            $this->__apilog['result']['msg'] = '没有订单：' . $sdf['order_bn'];
            return false;
        }
        $sdf['order'] = $tgOrder[0];
        $paymentModel = app::get('ome')->model('payments');
        $paymentFilter = array(
            'payment_bn' => $sdf['payment_bn'],
            'shop_id' => $this->__channelObj->channel['shop_id']
        );
        $tgPayment = $paymentModel->getList('payment_id,money,t_begin', $paymentFilter);
        if (empty($tgPayment)) {
            $this->__apilog['result']['msg'] = '没有支付单：' . $sdf['payment_bn'];
            return false;
        }
        $sdf['payment'] = $tgPayment[0];
        return $sdf;
    }
}