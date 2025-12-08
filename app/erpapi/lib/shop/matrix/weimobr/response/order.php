<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Created by PhpStorm.
 * User: ghc
 * Date: 18/10/10
 * Time: 上午10:51
 */
class erpapi_shop_matrix_weimobr_response_order extends erpapi_shop_response_order{
    protected $_update_accept_dead_order = true;

    /**
     * 订单是否创建
     * @return bool|void
     */

    protected function _canAccept(){
        if($this->_ordersdf['shipping']['shipping_name'] == '自提') {
            $this->__apilog['result']['msg'] = '到店自提订单暂不支持';
            return false;
        }
        if($this->_ordersdf['is_delivery'] == 'N') {
            $this->__apilog['result']['msg'] = '不发货订单不接收';
            return false;
        }
       
        return parent::_canAccept();
    }

    protected function _analysis()
    {
        parent::_analysis();
        //买家实付字段名
        $this->_ordersdf['coupon_actuallypay_field'] = 'extend_item_list/payAmount';
    }

    /**
     * @return array
     */
    protected function get_update_components()
    {
        $components = array('markmemo','marktype');

        if ($this->_ordersdf['pay_status'] != $this->_tgOrder['pay_status']) {
            $components[] = 'master';
        }

        // 如果没有收货人信息，
        if (!$this->_tgOrder['consignee']['name'] || !$this->_tgOrder['consignee']['area'] || !$this->_tgOrder['consignee']['addr']) {
            $components[] = 'consignee';
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

    protected function get_create_plugins()
    {
        $plugins = parent::get_create_plugins();

        $plugins[] = 'weimobvo2o';
        

        return $plugins;
    }
}