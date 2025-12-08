<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_wdwd_response_order extends erpapi_shop_response_order
{
    protected $_update_accept_dead_order = true;

    protected function get_update_components()
    {
        $components = array('markmemo','custommemo','marktype');

        if ( ($this->_ordersdf['pay_status'] != $this->_tgOrder['pay_status']) || ($this->_ordersdf['shipping']['is_cod']=='true' && $this->_ordersdf['status'] == 'dead') ) {
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
        if ($this->_ordersdf['status'] == 'dead' && $this->_ordersdf['shipping']['is_cod'] != 'true') $this->_ordersdf['pay_status'] = '5';

        if ($this->_ordersdf['status'] == 'finish' && $this->_ordersdf['ship_status'] == '0') $this->_ordersdf['status'] = 'active';

        parent::_analysis();
        // 重新计算优惠，兼容分销王将商品优惠，打在订单优惠上
        // 验证订单金额是否正确
        $total_amount = (float) $this->_ordersdf['cost_item'] 
                                + (float) $this->_ordersdf['shipping']['cost_shipping'] 
                                + (float) $this->_ordersdf['shipping']['cost_protect'] 
                                + (float) $this->_ordersdf['discount'] 
                                + (float) $this->_ordersdf['cost_tax'] 
                                + (float) $this->_ordersdf['payinfo']['cost_payment'] 
                                - (float) $this->_ordersdf['pmt_goods'] 
                                - (float) $this->_ordersdf['pmt_order'];
        if(0 != bccomp($total_amount, $this->_ordersdf['total_amount'],3)){
            $cost_item = 0;
            foreach ($this->_ordersdf['order_objects'] as $objkey => $object){
                foreach ($object['order_items'] as $itemkey => $item){
                    $cost_item += (float) $item['amount'];
                }
            }

            $total_amount = $total_amount - (float) $this->_ordersdf['cost_item'] + $cost_item;

            if(0 == bccomp($total_amount, $this->_ordersdf['total_amount'],3)){
                $this->_ordersdf['cost_item'] = $cost_item;
            }
        }
    }
}
