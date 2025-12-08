<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 接收订单处理
 *
 * @author wangbiao@shopex.cn
 * @version $Id: Z
 */
class erpapi_shop_matrix_dewu_response_order extends erpapi_shop_response_order
{
    protected $_update_accept_dead_order = true;
    
    /**
     * _canAccept
     * @return mixed 返回值
     */

    public function _canAccept()
    {
        
        $presalesetting = app::get('ome')->getConf('ome.order.presale');
        
        if(in_array($this->_ordersdf['trade_type'],array('step')) || in_array($this->_ordersdf['t_type'],array('step'))){
            $this->_ordersdf['order_type'] = 'presale';
            
        }
        
        if(app::get('presale')->is_installed() && $presalesetting == '1' && $this->_ordersdf['order_type'] == 'presale'){
            if(in_array($this->_ordersdf['step_trade_status'],array('FRONT_PAID_FINAL_NOPAID'))){
                $this->_accept_unpayed_order = true;
            }
        }
        
        if(($this->_accept_unpayed_order==false && in_array($this->_ordersdf['step_trade_status'],array('FRONT_NOPAID_FINAL_NOPAID','FRONT_PAID_FINAL_NOPAID'))) || ($this->_accept_unpayed_order == true && in_array($this->_ordersdf['step_trade_status'],array('FRONT_NOPAID_FINAL_NOPAID')))){
            
            $this->__apilog['result']['msg'] = '定金未付尾款未付或定金已付尾款未付订单不接收';
            return false;
        }
        
        
        return parent::_canAccept();
    }
    
    protected function get_update_plugins()
    {
        $plugins = parent::get_update_plugins();
        
        if ( (in_array($this->_tgOrder['order_type'], array('presale')))
            && ($this->_tgOrder['pay_status'] == '3' || $this->_tgOrder['total_amount'] != $this->_ordersdf['total_amount'])
            && $this->_tgOrder['cost_item'] == $this->_ordersdf['cost_item']
            && $this->_ordersdf['step_trade_status'] == 'FRONT_PAID_FINAL_PAID')
        {
            $plugins[] = 'payment';
        }
        
        return $plugins;
    }
    
    protected function get_update_components()
    {
        $components = array('markmemo','marktype','custommemo');
        
        if ($this->_ordersdf['pay_status'] != $this->_tgOrder['pay_status'])
        {
            // 如果没有退款申请单,以前端为主
            $refundApply = app::get('ome')->model('refund_apply')->getList('apply_id', array('order_id'=>$this->_tgOrder['order_id'], 'status|noequal'=>'3'));
            if(!$refundApply){
                $components[] = 'master';
            }
        }
        
        //如果没有收货人信息
        if (!$this->_tgOrder['consignee']['name'] || !$this->_tgOrder['consignee']['area'] || !$this->_tgOrder['consignee']['addr'])
        {
            $components[] = 'consignee';
        }
    
        //查看是否预售订单已支付状态
        if(in_array($this->_tgOrder['order_type'], array('presale'))){
            $components[] = 'tbpresale';
        }
        if ( (in_array($this->_tgOrder['order_type'], array('presale')))
            && ($this->_tgOrder['pay_status'] == '3' || $this->_tgOrder['total_amount'] != $this->_ordersdf['total_amount'])
            && $this->_tgOrder['cost_item'] == $this->_ordersdf['cost_item']
            && $this->_ordersdf['step_trade_status'] == 'FRONT_PAID_FINAL_PAID')
        {
            if(!array_search('master', $components)) $components[] = 'master';
            $components[] = 'items';
        }
        
        return $components;
    }

    protected function _operationSel()
    {
        parent::_operationSel();
        
        // 更新收货地址
        if ($this->_tgOrder)
        {
            if (!$this->_tgOrder['consignee']['name'] || !$this->_tgOrder['consignee']['area'] || !$this->_tgOrder['consignee']['addr']) {
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
    
    protected function get_convert_components()
    {
        $components = parent::get_convert_components();
        
        $components[] = 'tbpresale';
        return $components;
    }
}
