<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 微盟订单处理
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_weimob_response_order extends erpapi_shop_response_order
{
    protected $_update_accept_dead_order = true;

    protected function _canAccept(){
        if($this->_ordersdf['shipping']['shipping_name'] == '自提') {
            $this->__apilog['result']['msg'] = '到店自提订单暂不支持';
            return false;
        }
        return parent::_canAccept();
    }

    protected function get_update_components()
    {
        $components = array('markmemo','marktype');
        
        if ($this->_ordersdf['pay_status'] != $this->_tgOrder['pay_status']) {
            $components[] = 'master';
        }
        return $components;
    }

    protected function _operationSel()
    {
        parent::_operationSel();

        if ($this->_tgOrder) {
            $this->_operationSel = 'update';
        }  
    }

}
