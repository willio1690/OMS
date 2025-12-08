<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Class erpapi_shop_matrix_website_v2_response_order
 */
class erpapi_shop_matrix_website_v2_response_order extends erpapi_shop_matrix_website_response_order
{
    /**
     * 可接收未付款订单
     *
     * @var string
     **/
    protected $_accept_unpayed_order = true;
    
    /**
     * 创建订单的插件
     *
     * @return void
     * @author
     **/

    protected function get_create_plugins()
    {
        $plugins = parent::get_create_plugins();
        
        // 如果是0元订单，注销支付单插件
        if (bccomp('0.000', $this->_ordersdf['total_amount'],3) == 0) {
            $key = array_search('payment', $plugins);
            if ($key !== false) {
                unset($plugins[$key]);
            }
        }
        
        if (false === array_search('orderextend', $plugins)) {
            $plugins[] = 'orderextend';
        }
        
        return $plugins;
    }
    
    protected function get_update_plugins()
    {
        $plugins = parent::get_update_plugins();
        
        $plugins[] = 'promotion';
        $plugins[] = 'payment';
        $plugins[] = 'refundapply';
        $plugins[] = 'cod';
        
        return $plugins;
    }
    
    protected function _analysis()
    {
        parent::_analysis();
        
        // 判断是否有退款
        if ($this->_ordersdf['payed'] > $this->_ordersdf['total_amount']) {
            $this->_ordersdf['pay_status'] = '6';
            $this->_ordersdf['pause']      = 'true';
        }
    }
}