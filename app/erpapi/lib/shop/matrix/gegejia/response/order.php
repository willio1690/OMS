<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2018/4/25
 * @describe 订单处理
 */

class erpapi_shop_matrix_gegejia_response_order extends erpapi_shop_response_order
{
    protected $_update_accept_dead_order = true;

    protected function _analysis() {
        if($this->_ordersdf['check_status'] != '2' || !in_array($this->_ordersdf['freeze_status'], array('0', '2'))) {
            $this->_ordersdf['mark_text'] .= '平台冻结订单';
        }
        parent::_analysis();
    }

    //check_status str 订单审核状态，1：待审核，2：审核通过，3：审核不通过，只有checkStatus=2的订单才能发货
    //freeze_status str 订单冻结状态，冻结状态；0：未冻结，1：已冻结，2：已解冻，3：永久冻结，只有freezeStatus=0、2状态的订单才能发货
    protected function _canCreate() {
        if($this->_ordersdf['check_status'] != '2'){
            $this->__apilog['result']['msg'] = '不是审核通过订单不接收';
            return false;
        }
        if(!in_array($this->_ordersdf['freeze_status'], array('0', '2'))) {
            $this->__apilog['result']['msg'] = '冻结订单不接收';
            return false;
        }
        return parent::_canCreate();
    }
    protected function get_update_components()
    {
        $components = array('custommemo', 'markmemo','consignee');
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
        $update = false;
        // 没有收货地址的单子允许更新
        if ($update === false && $this->_tgOrder) {

            list(,,$area_id) = explode(':', $this->_tgOrder['consignee']['area']);

            if (!$area_id || !$this->_tgOrder['consignee']['addr']) $update = true;
        }
        // 即不是更新，也是不是创建,才做这样逻辑判断
        if (!$this->_operationSel && $update) {
            $this->_operationSel = 'update';
        }
    }


}
