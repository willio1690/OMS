<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_alibaba4ascp_response_order_b2c extends erpapi_shop_matrix_alibaba_response_order
{
    protected $_update_accept_dead_order = true;

    protected function _analysis()
    {
        parent::_analysis();

        $total_amount = (float) $this->_ordersdf['cost_item']
            + (float) $this->_ordersdf['shipping']['cost_shipping']
            + (float) $this->_ordersdf['shipping']['cost_protect']
            + (float) $this->_ordersdf['discount']
            + (float) $this->_ordersdf['cost_tax']
            + (float) $this->_ordersdf['payinfo']['cost_payment']
            - (float) $this->_ordersdf['pmt_goods']
            - (float) $this->_ordersdf['pmt_order'];
        if(0 != bccomp($total_amount, $this->_ordersdf['total_amount'],3)){
            $pmt_order =   $total_amount - $this->_ordersdf['total_amount'];#差额全放订单总优惠上
            if($pmt_order > 0){
                $this->_ordersdf['pmt_order'] = $pmt_order;
            }
        }
    }

    protected function get_update_components()
    {
        $components = parent::get_update_components();

        // 到付取消
        if ( ($this->_ordersdf['shipping']['is_cod'] == 'true' && $this->_ordersdf['status'] == 'dead') 
                || ($this->_ordersdf['shipping']['is_cod'] != 'true' && $this->_ordersdf['total_amount'] != $this->_ordersdf['payed'])
            ) {
            $components[] = 'master';
        }

        return $components;
    }
}
