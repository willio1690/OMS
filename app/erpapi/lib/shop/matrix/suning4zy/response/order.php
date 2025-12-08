<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 苏宁自营
 * Class erpapi_shop_matrix_suning4zy_response_order
 */
class erpapi_shop_matrix_suning4zy_response_order extends erpapi_shop_response_order
{
    protected $_update_accept_dead_order = true;


    protected function get_update_components()
    {
        $components = array('markmemo','marktype','custommemo');

        if (($this->_ordersdf['pay_status'] != $this->_tgOrder['pay_status']) ||($this->_ordersdf['shipping']['is_cod']=='true' && $this->_ordersdf['status'] == 'dead')) {
            $refundApply = app::get('ome')->model('refund_apply')->getList('apply_id',array('order_id'=>$this->_tgOrder['order_id'],'status|noequal'=>'3'));
            // 如果没有退款申请单，以前端为主
            if (!$refundApply) {
                $components[] = 'master';
            }
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
