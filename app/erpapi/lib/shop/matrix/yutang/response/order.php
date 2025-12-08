<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * Created by PhpStorm.
 * User: gehuachun
 * Date: 2018-12-10
 * Time: 13:50
 */
class erpapi_shop_matrix_yutang_response_order extends erpapi_shop_response_order
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