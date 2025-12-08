<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2016/12/15
 * @describe 订单处理
 */

class erpapi_shop_matrix_kaola_response_order extends erpapi_shop_response_order
{
    protected $_update_accept_dead_order = true;
    protected function get_update_components()
    {
        $components = array();
        if (($this->_ordersdf['pay_status'] != $this->_tgOrder['pay_status']) ||($this->_ordersdf['shipping']['is_cod']=='true' && $this->_ordersdf['status'] == 'dead')) {
            $components[] = 'master';
        }
        return $components;
    }
}
