<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Datetime: 2022/3/3
 */

class erpapi_shop_matrix_yunji4pop_response_order extends erpapi_shop_response_order
{
    protected $_update_accept_dead_order = true;
    
    protected function get_update_components()
    {
        $components = array('markmemo', 'marktype');
        
        if ($this->_ordersdf['pay_status'] != '1') {
            $refundApply = app::get('ome')->model('refund_apply')->getList('apply_id', array('order_id' => $this->_tgOrder['order_id'], 'status|noequal' => '3'));
            // 如果没有退款申请单，以前端为主
            if (!$refundApply) {
                $components[] = 'master';
            }
        }
        
        return $components;
    }
}
