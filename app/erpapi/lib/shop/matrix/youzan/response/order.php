<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 微盟订单处理
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_youzan_response_order extends erpapi_shop_response_order
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
        if ($this->_ordersdf['pay_status'] != $this->_tgOrder['pay_status'] || ($this->_ordersdf['shipping']['is_cod']=='true' && $this->_ordersdf['status'] == 'dead') ) {
            $refundApply = app::get('ome')->model('refund_apply')->getList('apply_id',array('order_id'=>$this->_tgOrder['order_id'],'status|noequal'=>'3'));
            // 如果没有退款申请单，以前端为主
            if (!$refundApply) {
                $components[] = 'master';
            }
        }

        return $components;
    }

    protected function _analysis()
    {
        parent::_analysis();

        if ($this->_ordersdf['pmt_goods'] == 0) {
            $total_amount = (float) $this->_ordersdf['cost_item']
                + (float) $this->_ordersdf['shipping']['cost_shipping']
                + (float) $this->_ordersdf['shipping']['cost_protect']
                + (float) $this->_ordersdf['discount']
                + (float) $this->_ordersdf['cost_tax']
                + (float) $this->_ordersdf['payinfo']['cost_payment']
                - (float) $this->_ordersdf['pmt_goods']
                - (float) $this->_ordersdf['pmt_order'];
            if(0 != bccomp($total_amount, $this->_ordersdf['total_amount'],3)){

                $pmt_order = $total_amount - $this->_ordersdf['total_amount']; #差额全放订单总优惠上
                if($pmt_order > 0){
                    $this->_ordersdf['pmt_order'] = $pmt_order;
                }
            }
        }
        if ($this->_ordersdf['trade_from'] && $this->_ordersdf['trade_from'] != 'NORMAL') {
            $this->_ordersdf['order_source'] = $this->_ordersdf['trade_from'];
        }
    }
}
