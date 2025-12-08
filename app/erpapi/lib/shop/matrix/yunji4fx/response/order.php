<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author k 2017/10/23
 * @describe 订单处理
 */

class erpapi_shop_matrix_yunji4fx_response_order extends erpapi_shop_response_order
{
    protected $_update_accept_dead_order = true;

    protected function get_update_components()
    {
        $components = array('markmemo','marktype');

        $rs = app::get('ome')->model('order_extend')->getList('extend_status',array('order_id'=>$this->_tgOrder['order_id']));
        // 如果ERP收货人信息未发生变动时，则更新淘宝收货人信息
        if ($rs[0]['extend_status'] != 'consignee_modified') {
            $components[] = 'consignee';
        }
        if (($this->_ordersdf['pay_status'] != $this->_tgOrder['pay_status']) || ($this->_ordersdf['shipping']['is_cod'] == 'true' && $this->_ordersdf['status'] == 'dead')) {
            $refundApply = app::get('ome')->model('refund_apply')->getList('apply_id', array('order_id' => $this->_tgOrder['order_id'], 'status|noequal' => '3'));
            // 如果没有退款申请单，以前端为主
            if (!$refundApply) {
                $components[] = 'master';
            }
        }
        return $components;
    }
}
