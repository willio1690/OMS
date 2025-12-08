<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 苏宁订单处理
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_shopex_bbc_response_order extends erpapi_shop_matrix_shopex_response_order
{
    protected $_update_accept_dead_order = true;

    protected function _analysis()
    {
        parent::_analysis();

        foreach ($this->_ordersdf['order_objects'] as $key_obj => $value_obj) {
            foreach ($value_obj['order_items'] as $key_item => $value_item) {
                $this->_ordersdf['order_objects'][$key_obj]['order_items'][$key_item]['item_type'] = ($value_item['item_type'] == 'goods') ? 'product' : $value_item['item_type'];
            }
        }
    }

}
