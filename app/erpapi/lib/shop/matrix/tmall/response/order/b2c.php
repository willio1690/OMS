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
class erpapi_shop_matrix_tmall_response_order_b2c extends erpapi_shop_matrix_tmall_response_order
{
    protected $_update_accept_dead_order = true;

    protected function _analysis()
    {
        parent::_analysis();

        $pmt = bcadd($this->_ordersdf['pmt_goods'],$this->_ordersdf['pmt_order'],3);
        if (is_array($this->_ordersdf['order_objects']) && count($this->_ordersdf['order_objects']) == 1 && bccomp($this->_ordersdf['cost_item'],$pmt,3) == -1 ) {
            $this->_ordersdf['pmt_order'] = '0';
        }
    }

    protected function get_update_components()
    {
        $components = parent::get_update_components();

        // 到付取消
        if($this->_ordersdf['shipping']['is_cod'] == 'true' && $this->_ordersdf['status'] != 'active'){
            $components[] = 'master';
        }

        return $components;
    }

    protected function _canUpdate()
    {
        /*if ( $this->_ordersdf['status'] == 'dead' && $this->_ordersdf['shipping']['is_cod'] != 'true') {
            $this->__apilog['result']['msg'] = '取消订单不接收';
            return false;
        }*/

        return parent::_canUpdate();
    }
}
