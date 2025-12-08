<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_hupu_response_order extends erpapi_shop_response_order
{
    protected $_update_accept_dead_order = true;


    protected function get_update_components(){
        $components = array('markmemo','marktype','custommemo');

        if (($this->_ordersdf['pay_status'] != $this->_tgOrder['pay_status']) ||($this->_ordersdf['shipping']['is_cod']=='true' && $this->_ordersdf['status'] == 'dead')) {
            $refundApply = app::get('ome')->model('refund_apply')->getList('apply_id',array('order_id'=>$this->_tgOrder['order_id'],'status|noequal'=>'3'));
            // 如果没有退款申请单，以前端为主
            if (!$refundApply) {
                $components[] = 'master';
            }
        }

        // 更新收货地址
        if($this->_tgOrder && $this->_tgOrder['process_status']=='unconfirmed'){
            $rs = app::get('ome')->model('order_extend')->getList('extend_status',array('order_id'=>$this->_tgOrder['order_id']));
            if ($rs[0]['extend_status'] != 'consignee_modified') {
                $components[] = 'consignee';
            }
        }
        
        return $components;
    }

    protected function _analysis()
    {
        parent::_analysis();

        $this->_ordersdf['shipping']['shipping_name'] = '';

        // 虚拟发货
        if ($this->_ordersdf['shipping']['is_virtual_delivery'] == 'true') {
            $this->_ordersdf['shipping']['shipping_name'] = 'virtual_delivery';
        }
    }

    /**
     * status_update
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function status_update($sdf)
    {
        $this->__apilog['original_bn']    = $sdf['order_bn'];
        $this->__apilog['title']          = '修改订单状态['.$sdf['order_bn'].']';


        // 只接收作废订单
        if ($sdf['status'] == '') {
            $this->__apilog['result']['msg'] = '订单状态不能为空';
            return false;
        }

        if ($sdf['status'] != 'dead') {
            $this->__apilog['result']['msg'] = '不接收除作废以外的其他状态';
            return false;
        }

        // 读取订单
        $order = app::get('ome')->model('orders')->db_dump(array ('order_bn'=>$sdf['order_bn'], 'shop_id' => $this->__channelObj->channel['shop_id']));

        if (!$order) {
            $this->__apilog['result']['msg'] = 'ERP订单不存在';
            return false;
        }

        if ($order['status'] != 'active') {
            $this->__apilog['result']['msg'] = '订单非活动状态';
            return false;
        }

        if ($order['ship_status'] != '0') {
            $this->__apilog['result']['msg'] = '订单已经发货';
            return false;
        }

        $data = array (
            'order_id'    => $order['order_id'],
            'shop_id'     => $order['shop_id'],
            'refundMoney' => $order['payed'],
            'status'      => $sdf['status'],
            'payed'       => '0',
            'pay_status'  => $order['payed'] > '0' ? '5' : '0',
        );

        return $data;
    }
}
