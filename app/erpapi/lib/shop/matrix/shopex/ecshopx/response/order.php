<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_shopex_ecshopx_response_order extends erpapi_shop_matrix_shopex_response_order
{
    protected $_update_accept_dead_order = true;

    

    protected function _analysis()
    {
        parent::_analysis();
        if($this->_ordersdf['shipping']['shipping_name'] == 'STORE_SELF_FETCH' || $this->_ordersdf['shipping']['shipping_name'] == 'STORE_TONGCHEN_EXPRESS'){
             $this->_setPlatformDelivery();
        }
    }

    protected function get_create_plugins()
    {
        $plugins = parent::get_create_plugins();

        $plugins[] = 'orderextend';
        

        return $plugins;
    }
}
