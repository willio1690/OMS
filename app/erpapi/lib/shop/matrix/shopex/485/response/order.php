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
class erpapi_shop_matrix_shopex_485_response_order extends erpapi_shop_matrix_shopex_response_order
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

    /**
     * 更新接收,以前端状态为主
     *
     * @return void
     * @author 
     **/

    protected function _canUpdate()
    {
        if ($this->__channelObj->get_ver() == '1' && $this->_ordersdf['status'] == 'dead') {
            $this->__apilog['result']['msg'] = '取消订单不接收';
            return false;
        }

        return parent::_canUpdate();
    }
}
