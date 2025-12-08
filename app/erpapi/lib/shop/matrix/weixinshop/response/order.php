<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Datetime: 2022/4/11
 * @Describe: 订单接收类
 */
class erpapi_shop_matrix_weixinshop_response_order extends erpapi_shop_response_order
{

    protected $_update_accept_dead_order = true;

    protected function get_update_components()
    {
        $components = array('markmemo', 'custommemo', 'marktype');
        
        //如果没有收货人信息
        if (!$this->_tgOrder['consignee']['name'] || !$this->_tgOrder['consignee']['area'] || !$this->_tgOrder['consignee']['addr'] || !$this->_tgOrder['consignee']['mobile']) {
            $components[] = 'consignee';
        }
    
        if ( ($this->_ordersdf['pay_status'] != $this->_tgOrder['pay_status']) ||($this->_ordersdf['shipping']['is_cod']=='true' && $this->_ordersdf['status'] == 'dead') ) {
            $refundApply = app::get('ome')->model('refund_apply')->getList('apply_id',array('order_id'=>$this->_tgOrder['order_id'],'status|noequal'=>'3'));
            // 如果没有退款申请单，以前端为主
            if (!$refundApply) {
                $components[] = 'master';
            }
        }
        
        return $components;
    }
}