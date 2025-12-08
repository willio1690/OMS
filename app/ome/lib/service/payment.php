<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_service_payment{

    /**
     * __construct
     * @param mixed $app app
     * @return mixed 返回值
     */
    public function __construct(&$app)
    {
        $this->app = $app;
    }

    /**
     * 添加付款单
     * @access public
     * @param int $payment_id 付款单ID
     */
    public function payment($payment_id){
        $paymentModel = $this->app->model('payments');
        $payment = $paymentModel->dump($payment_id);
        $res = kernel::single('erpapi_router_request')->set('shop', $payment['shop_id'])->finance_addPayment($payment);
    }

    /**
     * 付款单支付请求
     * @access public
     * @param int $sdf 请求数据
     */
    public function payment_request($payment){
        $res = kernel::single('erpapi_router_request')->set('shop', $payment['shop_id'])->finance_addPayment($payment);
    }

    /**
     * 付款单状态更新
     * @access public
     * @param int $payment_id 付款单ID
     */
    public function status_update($payment_id){

    }
}