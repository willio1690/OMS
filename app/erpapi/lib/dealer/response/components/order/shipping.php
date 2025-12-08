<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
* 配送方式
*
* @author chenping<chenping@shopex.cn>
* @version $Id: shipping.php 2013-3-12 17:23Z
*/
class erpapi_dealer_response_components_order_shipping extends erpapi_dealer_response_components_order_abstract
{
    /**
     * 创建订单数据格式转换
     *
     * @return void
     * @author 
     **/

    public function convert()
    {
        if ($this->_platform->_ordersdf['shipping']) {
            $this->_platform->_newOrder['shipping_name'] = $this->_platform->_ordersdf['shipping']['shipping_name'];
            $this->_platform->_newOrder['cost_freight'] = (float)$this->_platform->_ordersdf['shipping']['cost_shipping'];
            $this->_platform->_newOrder['is_cod'] = $this->_platform->_ordersdf['shipping']['is_cod'] == 'true' ? 'true' : 'false';
        }
    }
    
    /**
     * 更新订单数据格式转换
     *
     * @return void
     **/
    public function update()
    {
        if ($this->_platform->_ordersdf['shipping']) {
            //platform shipping
            $shipping = array(
                'shipping_name' => $this->_platform->_ordersdf['shipping']['shipping_name'],
                'cost_freight' => (float)$this->_platform->_ordersdf['shipping']['cost_shipping'],
                'is_cod' => ($this->_platform->_ordersdf['shipping']['is_cod'] == 'true' ? 'true' : 'false'),
            );
            $shipping = array_filter($shipping, array($this, 'filter_null'));
            
            //erp shipping
            $erpShipping = array(
                'shipping_name' => $this->_platform->_tgOrder['shipping_name'],
                'cost_freight' => (float)$this->_platform->_tgOrder['cost_freight'],
                'is_cod' => $this->_platform->_tgOrder['is_cod'],
            );
            
            //diff
            $diff = array_udiff_assoc($shipping, $erpShipping, array($this,'comp_array_value'));
            if($diff) {
                //merge
                $this->_platform->_newOrder = array_merge((array)$this->_platform->_newOrder, $diff);
            }
        }
    }
}