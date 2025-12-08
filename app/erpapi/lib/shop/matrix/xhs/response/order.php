<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 小红书订单 by wangjianjun 20170815
 */
class erpapi_shop_matrix_xhs_response_order extends erpapi_shop_response_order
{
    
    protected $_update_accept_dead_order = true;
    
    //平台订单状态
    protected $_sourceStatus = array(
        '1' => 'WAIT_BUYER_PAY', #已下单待付款 
        '2' => 'PAID_DEALING', #已支付处理中 
        '3' => 'CLEAR_CUSTOMS', #清关中 
        '4' => 'WAIT_SELLER_SEND_GOODS', #待发货 
        '5' => 'SELLER_CONSIGNED_PART', #部分发货 
        '6' => 'WAIT_BUYER_CONFIRM_GOODS', #待收货 
        '7' => 'TRADE_FINISHED', #已完成 
        '8' => 'TRADE_CLOSED_BY_TAOBAO', #已关闭 
        '9' => 'TRADE_CLOSED', #已取消 
        '10' => 'TRADE_RETURNING', #换货申请中
    );

    protected function get_update_components()
    {
        $components = array('markmemo', 'custommemo', 'marktype', 'tax', 'consignee', 'booltype');
        
        if (($this->_ordersdf['pay_status'] != $this->_tgOrder['pay_status']) || ($this->_ordersdf['shipping']['is_cod'] == 'true' && $this->_ordersdf['status'] == 'dead')) {
            $refundApply = app::get('ome')->model('refund_apply')->getList('apply_id',
                array('order_id' => $this->_tgOrder['order_id'], 'status|noequal' => '3'));
            // 如果没有退款申请单，以前端为主
            if (!$refundApply) {
                $components[] = 'master';
            }
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
    
        if ($this->_tgOrder) {
            $rs = app::get('ome')->model('order_extend')->getList('extend_status,bool_extendstatus', array('order_id' => $this->_tgOrder['order_id']));
            // 如果ERP收货人信息未发生变动时，则更新淘宝收货人信息
            if ($rs[0]['extend_status'] != 'consignee_modified') {
                $components[] = 'consignee';
            }
        }
        
        return $components;
    }
    
    protected function _canCreate()
    {
        if ('false' == app::get('ome')->getConf('ome.platform.rporder.xiaohongshu') && 'RP' == substr($this->_ordersdf['order_bn'],
                0, 2)) {
            $this->__apilog['result']['msg'] = '小红书补发单不接收';
            return false;
        }
        
        return parent::_canCreate();
    }

    protected function _canUpdate()
    {
        if ($this->_ordersdf['ship_status'] == '1'){
            $this->__apilog['result']['msg'] = '平台已发货，不做更新';
            return false;
        }
        return parent::_canUpdate();
    }
    
    protected function _analysis()
    {
        parent::_analysis();
        //买家实付字段名
        $this->_ordersdf['coupon_actuallypay_field'] = 'extend_item_list/totalPayAmount';
        $this->_ordersdf['coupon_actuallypay_field_unit'] = 100;
        if (empty($this->_ordersdf['consignee']['name'])) {
            $this->_ordersdf['consignee']['name'] = $this->_ordersdf['extend_field']['openAddressId'];
        }
    
        if (empty($this->_ordersdf['consignee']['uname'])) {
            $this->_ordersdf['consignee']['uname'] = $this->_ordersdf['extend_field']['openAddressId'];
        }
    
        if (empty($this->_ordersdf['consignee']['telephone'])) {
            $this->_ordersdf['consignee']['telephone'] = $this->_ordersdf['extend_field']['openAddressId'];
        }
    
        if (empty($this->_ordersdf['consignee']['addr'])) {
            $this->_ordersdf['consignee']['addr'] = $this->_ordersdf['extend_field']['openAddressId'];
        }
    
        if (empty($this->_ordersdf['consignee']['mobile'])) {
            $this->_ordersdf['consignee']['mobile'] = $this->_ordersdf['extend_field']['openAddressId'];
        }
        
        if (empty($this->_ordersdf['consignee']['name'])) {
            $this->_ordersdf['is_risk'] = 'true';
        }else{
            $this->_ordersdf['is_risk'] = 'false';
        }
        if($this->_ordersdf['cn_info'] && $this->_ordersdf['cn_info']['es_date']) {
            $this->_ordersdf['consignee']['r_time'] = $this->_ordersdf['cn_info']['es_date'] . ' ' . $this->_ordersdf['cn_info']['es_range'];
        }
        
        //openAddressId
        if($this->_ordersdf['extend_field']['openAddressId']){
            $this->_ordersdf['index_field']['openAddressId'] = $this->_ordersdf['extend_field']['openAddressId'];
        }

        $author_info = [];
        // 订单达人
        foreach ($this->_ordersdf['order_objects'] as $ook => $oov) {
            foreach ($oov['order_items'] as $oik => $oiv) {
                if (isset($oiv['extend_item_list']) && $oiv['extend_item_list']) {
                    if ($oiv['extend_item_list']['kol_id']) {
                        $this->_ordersdf['order_objects'][$ook]['authod_id']    = $oiv['extend_item_list']['kol_id'];
                        $author_info[$oov['oid']]['authod_id'] = $oiv['extend_item_list']['kol_id'];
                    }
                    if ($oiv['extend_item_list']['kol_name']) {
                        $this->_ordersdf['order_objects'][$ook]['author_name']  = $oiv['extend_item_list']['kol_name'];
                        $author_info[$oov['oid']]['author_name'] = $oiv['extend_item_list']['kol_name'];
                    }
                }
            }
        }
        if ($author_info) {
            if (!isset($this->_ordersdf['extend_field'])) {
                $this->_ordersdf['extend_field'] = [];
            }
            $this->_ordersdf['extend_field']['is_host']     = true;
            $this->_ordersdf['extend_field']['author_info'] = $author_info;
        }
    }
    
    /**
     * _canAccept
     * @return mixed 返回值
     */

    public function _canAccept()
    {
        
        $presalesetting = app::get('ome')->getConf('ome.order.presale');
        
        
        if($this->_ordersdf['is_yushou'] == 'true' || in_array($this->_ordersdf['trade_type'],array('step')) || in_array($this->_ordersdf['t_type'],array('step'))){
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

    //创建订单的插件
    protected function get_create_plugins()
    {
        $plugins   = parent::get_create_plugins();
        $plugins[] = 'orderlabels';
        return $plugins;
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
        $plugins[] = 'orderextend';
        
        return $plugins;
    }
    
    
    protected function get_convert_components()
    {
        $components = parent::get_convert_components();
        
        $components[] = 'tbpresale';
        return $components;
    }
}
