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
class erpapi_shop_matrix_juanpi_response_order extends erpapi_shop_response_order
{
    protected $_update_accept_dead_order = true;

    protected function get_update_components()
    {
        $components = array('markmemo','marktype','custommemo');

        if(in_array($this->_tgOrder['process_status'], array('unconfirmed'))){
            $rs = app::get('ome')->model('order_extend')->getList('extend_status',array('order_id'=>$this->_tgOrder['order_id']));

            // 如果ERP收货人信息未发生变动时，则更新淘宝收货人信息
            if ($rs[0]['extend_status'] != 'consignee_modified') {
                $components[] = 'consignee';
            }
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

    protected function _analysis()
    {
        parent::_analysis();

        foreach($this->_ordersdf['order_objects'] as $obj_key=>$order_items){
            foreach((array) $order_items['order_items'] as $item_key=>$items){
                if(in_array($items['status'],array('close','refund_close'))){
                    $this->_ordersdf['order_objects'][$obj_key]['order_items'][$item_key]['status'] = 'close';
                } else if ($items['status'] == 'refunding') {
                    $this->_ordersdf['pay_status'] = '6';
                }
            }
        }

        $pmt_order = $this->_ordersdf['cost_item']-$this->_ordersdf['payed'];
        if($pmt_order > 0){
            $this->_ordersdf['pmt_order'] = $pmt_order;
        }

        if ($this->_ordersdf['status'] == 'finish' && $this->_ordersdf['shipping']['is_cod'] != 'true') $this->_ordersdf['status'] = 'active';
        if ($this->_ordersdf['status'] == 'dead' && $this->_ordersdf['shipping']['is_cod'] != 'true') $this->_ordersdf['pay_status'] = '5';
    }
}
