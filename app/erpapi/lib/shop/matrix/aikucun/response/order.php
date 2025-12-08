<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Created by PhpStorm.
 * User: qiudi
 * Date: 18/10/10
 * Time: 上午10:51
 */
class erpapi_shop_matrix_aikucun_response_order extends erpapi_shop_response_order{
    protected $_update_accept_dead_order = true;

    protected function get_update_components()
    {
        $components = array('markmemo','marktype','custommemo');

        if ( ($this->_ordersdf['shipping']['is_cod']=='true' && $this->_ordersdf['status'] == 'dead') || ($this->_ordersdf['shipping']['is_cod'] != 'true' && $this->_ordersdf['pay_status'] == '5'))
        {
            $components[] = 'master';
        }

        return $components;
    }

    protected function get_create_plugins()
    {
        $plugins = parent::get_create_plugins();

        $plugins[] = 'waybill';
        $plugins[] = 'orderextend';

        return $plugins;
    }

    protected function _analysis()
    {
        parent::_analysis();

        // 98代表商家自发
        if ($this->_ordersdf['shipping']['shipping_name'] == '98') {
            $this->_ordersdf['shipping']['shipping_name'] = '';
        }
    }

    /**
     * _canAccept
     * @return mixed 返回值
     */

    public function _canAccept()
    {
        

        if ($this->_ordersdf['consignee']['telephone'] == '分配中' || $this->_ordersdf['consignee']['mobile'] == '分配中'){
            $this->__apilog['result']['msg'] = '手机或电话 分配中不处理';
            return false;
        }

        return parent::_canAccept();
    }
}