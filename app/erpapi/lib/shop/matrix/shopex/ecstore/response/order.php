<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 苏宁订单处理
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_shopex_ecstore_response_order extends erpapi_shop_matrix_shopex_response_order
{
    protected $_update_accept_dead_order = true;

    protected function _analysis()
    {
        parent::_analysis();

        if ($this->_ordersdf['promotion_details'] && is_string($this->_ordersdf['promotion_details'])) {
            $this->_ordersdf['pmt_detail'] = array();
            $pmt_detail = json_decode($this->_ordersdf['promotion_details']);
            foreach ($pmt_detail as $key => $value) {
                $this->_ordersdf['pmt_detail'][$key]['pmt_describe'] = trim($value['promotion_name']);
                $this->_ordersdf['pmt_detail'][$key]['pmt_amount'] = trim($value['promotion_fee']);
            }
        }
    }

    protected function _operationSel()
    {
        parent::_operationSel();

        $lastmodify = kernel::single('ome_func')->date2time($this->_ordersdf['lastmodify']);
        if (empty($this->_operationSel) && $lastmodify == $this->_tgOrder['outer_lastmodify']) {
            if ($this->_tgOrder['pay_status'] == '0' && $this->_ordersdf['pay_status'] == '1' && 0 == bccomp($this->_ordersdf['total_amount'], 0,3)) {
                $this->_operationSel = 'update';
            }
        }
    }

    /**
     * 更新接收,以前端状态为主
     *
     * @return void
     * @author 
     **/

    protected function _canUpdate()
    {
        if ($this->__channelObj->get_ver() == '1' && $this->_ordersdf['status'] == 'dead') {
            $this->__apilog['result']['msg'] = '取消订单不接收';
            return false;
        }

        return parent::_canUpdate();
    }
}
