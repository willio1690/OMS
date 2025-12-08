<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * Class erpapi_shop_matrix_zkh_response_order
 */
class erpapi_shop_matrix_zkh_response_order extends erpapi_shop_response_order
{
    //平台订单状态
    protected $_sourceStatus = array(
        '0'  => 'WAIT_BUYER_PAY',  //待确认/待支付（订单创建完毕）
        '1'  => 'WAIT_BUYER_PAY',  //待确认/待支付（订单创建完毕）
        '2'  => 'WAIT_SELLER_SEND_GOODS', //已支付
        '3'  => 'WAIT_BUYER_PAY',
        '4'  => 'SELLER_READY_GOODS',//交期待审核
        '5'  => 'WAIT_BUYER_CONFIRM_GOODS',//交期已确认
        '6'  => 'SELLER_READY_GOODS',//交期未通过
        '7'  => 'TRADE_FINISHED',//线下确认
        '-1' => 'TRADE_CLOSED',  //已取消
    );
    
    protected $_update_accept_dead_order = true;
    
    protected function get_update_components()
    {
        $components = array('markmemo','marktype','custommemo');
    
        if (($this->_ordersdf['pay_status'] != $this->_tgOrder['pay_status']) || ($this->_ordersdf['shipping']['is_cod'] == 'true' && $this->_ordersdf['status'] == 'dead')) {
            $refundApply = app::get('ome')->model('refund_apply')->getList('apply_id',
                array('order_id' => $this->_tgOrder['order_id'], 'status|noequal' => '3'));
            // 如果没有退款申请单，以前端为主
            if (!$refundApply) {
                $components[] = 'master';
            }
        }
        
        return $components;
    }
    
    protected function _analysis()
    {
        // 支付单(兼容老版本)
        if(is_string($this->_ordersdf['payment_detail']))
            $payment_detail = json_decode($this->_ordersdf['payment_detail'],true);
        
        if(is_string($this->_ordersdf['payments']))
            $payments = $this->_ordersdf['payments'] ? json_decode($this->_ordersdf['payments'],true) : array();
        
        // 配送信息
        if(is_string($this->_ordersdf['shipping']))
            $shipping = json_decode($this->_ordersdf['shipping'],true);
        
        // 用支付单判断订单状态
        $payment_list = isset($payments) ? $payments : array($payment_detail);
        if ($payment_list[0] && $this->_ordersdf['pay_status']=='0' && $this->_ordersdf['payed']>0 && $shipping['is_cod'] =='true') {
            
            $payed = 0;
            foreach ($payment_list as $key => $value) {
                $payed += $value['money'];
            }
            
            if ($this->_ordersdf['total_amount'] <= $payed) {
                $this->_ordersdf['pay_status'] = '1';
            } elseif ($payed <= 0) {
                $this->_ordersdf['pay_status'] = '0';
            } else {
                $comp = bccomp(round($payed,3), $this->_ordersdf['total_amount'],3);
                if ($comp<0) {
                    $this->_ordersdf['pay_status'] = '3';
                } else {
                    $this->_ordersdf['pay_status'] = '1';
                }
            }
        }
        
        if (!$this->_ordersdf['createtime']) $this->_ordersdf['createtime'] = time();
        
        parent::_analysis();
        
        if ($this->_ordersdf['status'] == 'dead' && $this->_ordersdf['shipping']['is_cod'] != 'true')
        {
            $this->_ordersdf['pay_status'] = '5';
            $this->_ordersdf['payed'] = 0;
        }
    }
    
    protected function _canAccept()
    {
        if (in_array($this->_ordersdf['source_status'],['WAIT_BUYER_PAY'])) {
            $this->__apilog['result']['msg'] = '未确认订单,不接受';
            return false;
        }
        
        return parent::_canAccept();
    }
    
}
