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
class erpapi_shop_matrix_mogujie_response_order extends erpapi_shop_response_order
{
    protected $_update_accept_dead_order = true;

    protected function get_update_components()
    {
        $components = array('markmemo','marktype','custommemo');

        // 如果没有收货人信息，
        if (!$this->_tgOrder['consignee']['name'] || !$this->_tgOrder['consignee']['area'] || !$this->_tgOrder['consignee']['addr']) {
            $components[] = 'consignee';
        }

        if ($this->_ordersdf['pay_status'] != $this->_tgOrder['pay_status']) {
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

        // 更新收货地址
        if ($this->_tgOrder) {
            if (!$this->_tgOrder['consignee']['name'] 
                || !$this->_tgOrder['consignee']['area'] 
                || !$this->_tgOrder['consignee']['addr']) {
                $this->_operationSel = 'update';
            }
        }
    }

    protected function _analysis()
    {
        parent::_analysis();

        if ($this->_ordersdf['status'] == 'dead' && $this->_ordersdf['shipping']['is_cod'] != 'true'){
            $this->_ordersdf['pay_status'] = '5';
            $this->_ordersdf['payed'] = 0;
        } 
    }
}
