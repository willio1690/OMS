<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 订单处理
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_amazon_response_order extends erpapi_shop_response_order
{
    protected function get_update_components()
    {
        $components = array('markmemo','marktype','custommemo');
        return $components;
    }

    protected function _canAccept()
    {
        if ($this->_ordersdf['t_type'] == 'fenxiao' || $this->_ordersdf['order_source'] == 'taofenxiao') {
            $this->__apilog['result']['msg'] = '分销订单暂时不接收';
            return false;
        }

        if ($this->_ordersdf['trade_type'] == 'AFN') {
            $this->__apilog['result']['msg'] = '不接受配送方式为亚马逊配送的订单';
            return false;
        }

        if (empty($this->_ordersdf['consignee']['addr']) && empty($this->_ordersdf['consignee']['name'])) {
            $this->__apilog['result']['msg'] = '收货人信息不完整';
            return false;
        }

        return parent::_canAccept();
    }

    protected function _analysis()
    {
        parent::_analysis();

        $this->_ordersdf['self_delivery'] = $this->_ordersdf['shipping']['shipping_name'] == '卖家自行配送' ? 'true' : 'false';
    }
}
