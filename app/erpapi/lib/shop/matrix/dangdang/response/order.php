<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 订单处理
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_dangdang_response_order extends erpapi_shop_response_order
{
    protected $_update_accept_dead_order = true;

    protected function get_update_components()
    {
        $components = array('markmemo','marktype','custommemo');
        
        if ( ($this->_ordersdf['shipping']['is_cod'] == 'true' && $this->_ordersdf['status'] == 'dead')
             || ($this->_ordersdf['shipping']['is_cod'] != 'true' && $this->_ordersdf['pay_status'] == '5')
         ) {
            $components[] = 'master';
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
        if ($this->_ordersdf['t_type'] == 'fenxiao' || $this->_ordersdf['order_source'] == 'taofenxiao') {
            $this->__apilog['result']['msg'] = '分销订单暂时不接收';
            return false;
        }

        return parent::_canAccept();
    }

}
