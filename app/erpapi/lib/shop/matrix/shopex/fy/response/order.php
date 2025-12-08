<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_shopex_fy_response_order extends erpapi_shop_matrix_shopex_response_order
{
    protected $_update_accept_dead_order = true;

    protected function _analysis()
    {
        parent::_analysis();

        // 预售订单
        if ($this->_ordersdf['order_source'] == 'presale') $this->_ordersdf['is_presale'] = true;
        if($this->_ordersdf['shipping']['shipping_name'] == 'STORE_SELF_FETCH' || $this->_ordersdf['shipping']['shipping_name'] == 'STORE_TONGCHEN_EXPRESS'){
             $this->_setPlatformDelivery();
        }
    }
}
