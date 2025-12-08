<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 订单主表信息
 *
 * @author wangbiao@shopex.cn
 * @version 2024.04.11
 */
class erpapi_dealer_response_components_order_master extends erpapi_dealer_response_components_order_abstract
{
    /**
     * 创建订单数据格式转换
     * 
     * @return void
     */

    public function convert()
    {
        $funcObj = kernel::single('ome_func');
        
        //店铺
        $this->_platform->_newOrder['shop_id']   = $this->_platform->__channelObj->channel['shop_id'];
        $this->_platform->_newOrder['shop_type'] = $this->_platform->__channelObj->channel['shop_type'];
        
        //order_type
        $order_type = ($this->_platform->_ordersdf['order_type'] ? strtolower($this->_platform->_ordersdf['order_type']) : 'normal');
        
        //经销商ID、组织架构ID
        $bs_id = isset($this->_platform->__channelObj->channel['bs_id']) ? $this->_platform->__channelObj->channel['bs_id'] : 0;
        $cos_id = isset($this->_platform->__channelObj->channel['cos_id']) ? $this->_platform->__channelObj->channel['cos_id'] : 0;
        
        //订单主信息
        $this->_platform->_newOrder['createway']        = 'matrix';
        $this->_platform->_newOrder['source']           = 'matrix';
        $this->_platform->_newOrder['bs_id']            = intval($bs_id); //经销商ID
        $this->_platform->_newOrder['cos_id']           = intval($cos_id); //组织架构ID
        $this->_platform->_newOrder['plat_order_bn']    = $this->_platform->_ordersdf['order_bn']; //平台订单号
        $this->_platform->_newOrder['order_type']       = $order_type;
        $this->_platform->_newOrder['cost_item']        = (float)$this->_platform->_ordersdf['cost_item'];
        $this->_platform->_newOrder['discount']         = (float)$this->_platform->_ordersdf['discount'];
        $this->_platform->_newOrder['total_amount']     = (float)$this->_platform->_ordersdf['total_amount'];
        $this->_platform->_newOrder['pmt_goods']        = (float)$this->_platform->_ordersdf['pmt_goods'];
        $this->_platform->_newOrder['pmt_order']        = (float)$this->_platform->_ordersdf['pmt_order'];
        $this->_platform->_newOrder['cur_amount']       = (float)$this->_platform->_ordersdf['cur_amount'];
        $this->_platform->_newOrder['score_u']          = (float)$this->_platform->_ordersdf['score_u'];
        $this->_platform->_newOrder['score_g']          = (float)$this->_platform->_ordersdf['score_g'];
        $this->_platform->_newOrder['currency']         = $this->_platform->_ordersdf['currency'] ? $this->_platform->_ordersdf['currency'] : 'CNY';
        $this->_platform->_newOrder['status']           = $this->_platform->_ordersdf['status'];
        $this->_platform->_newOrder['weight']           = (float)$this->_platform->_ordersdf['weight'];
        $this->_platform->_newOrder['order_source']     = $this->_platform->_ordersdf['order_source'];
        $this->_platform->_newOrder['cur_rate']         = $this->_platform->_ordersdf['cur_rate'] ? $this->_platform->_ordersdf['cur_rate'] : 1;
        $this->_platform->_newOrder['title']            = $this->_platform->_ordersdf['title'];
        $this->_platform->_newOrder['source_status']    = $this->_platform->_ordersdf['source_status'];
        $this->_platform->_newOrder['coupons_name']     = $this->_platform->_ordersdf['coupons_name'];
        $this->_platform->_newOrder['order_bool_type']  = $this->_platform->_ordersdf['order_bool_type'];
        $this->_platform->_newOrder['change_sku']       = $this->_platform->_ordersdf['change_sku'];
        $this->_platform->_newOrder['oaid']             = $this->_platform->_ordersdf['extend_field']['oaid'];
        
        // 平台运单号
        if ($this->_platform->_ordersdf['shipping']['shipping_id']) {
            $this->_platform->_newOrder['logi_no'] = $this->_platform->_ordersdf['shipping']['shipping_id'];
        }
        
        if (isset($this->_platform->_ordersdf['self_delivery'])) $this->_platform->_newOrder['self_delivery'] = $this->_platform->_ordersdf['self_delivery'];
        
        //时间
        $this->_platform->_newOrder['download_time']    = time();
        $this->_platform->_newOrder['createtime']       = $funcObj->date2time($this->_platform->_ordersdf['createtime']);
        
        $outer_lastmodify = $this->_platform->_ordersdf['lastmodify'] ? $this->_platform->_ordersdf['lastmodify'] : time();
        $this->_platform->_newOrder['outer_lastmodify'] = $funcObj->date2time($outer_lastmodify);
        
        //平台支付信息
        $payment_cfg = $this->_platform->get_payment($this->_platform->_ordersdf['pay_bn'],$this->_platform->__channelObj->channel['node_type']);
        $this->_platform->_newOrder['pay_bn']      = $payment_cfg['pay_bn'];
        $this->_platform->_newOrder['pay_status'] =$this->_platform->_ordersdf['pay_status'];
        $this->_platform->_newOrder['payed']      = $this->_platform->_ordersdf['payed'];
        
        //计算支付状态与金额
        $payment_list = isset($this->_platform->_ordersdf['payments']) ? $this->_platform->_ordersdf['payments'] : (array)$this->_platform->_ordersdf['payment_detail'];
        if ($payment_list && is_array($payment_list)) {
            $total_amount = $this->_platform->_ordersdf['total_amount'];
            
            $pay_status = '0';
            $payed = '0.000';
            $paytime = null;
            
            foreach ($payment_list as $key => $value)
            {
                $payed += $value['money'];
                
                if ($value['pay_time']) {
                    $paytime = kernel::single('ome_func')->date2time($value['pay_time']);
                }
            }
            
            if ($total_amount <= $payed) {
                $pay_status = '1';
                if ($this->_platform->_ordersdf['order_type'] == 'presale' && $this->_platform->_ordersdf['order_source'] == 'jingdong') {
                    $payed = $total_amount; // 支付金额不应该大于订单总额 
                }
                
                if (!$paytime) $paytime = time();
            } elseif ($payed <= 0) {
                $pay_status = '0';
            } else {
                if (!$paytime) $paytime = time();
                
                $comp = bccomp(round($payed,3), $total_amount,3);
                
                $pay_status = $comp < 0 ? '3' : '1';
            }
            
            $this->_platform->_newOrder['pay_status'] = $pay_status;
            $this->_platform->_newOrder['payed']      = $payed;
            
            //支付时间
            if ($paytime){
                $this->_platform->_newOrder['paytime'] = intval($paytime);
            }
        }
        
        //支付详细信息
        if($this->_platform->_ordersdf['payment_detail']){
            $payment_detail = $this->_platform->_ordersdf['payment_detail'];
            unset($this->_platform->_ordersdf['payment_detail']);
            
            $this->_platform->_ordersdf['payment_detail'][] = $payment_detail;
            
            //支付方式
            $this->_platform->_newOrder['payment'] = $payment_detail['paymethod'];
        }
        
        if (in_array($this->_platform->_ordersdf['pay_status'], ['4', '5', '6', '7'])) {
            $this->_platform->_newOrder['pay_status'] = $this->_platform->_ordersdf['pay_status'];
            
            if ($this->_platform->_ordersdf['pay_status'] == '5') {
                $this->_platform->_newOrder['archive']          = '1';
                $this->_platform->_newOrder['process_status']   = 'cancel';
                $this->_platform->_newOrder['status']           = 'dead';
            }
        }
        
        //加密数据
        if($this->_platform->_ordersdf['index_field'] && is_array($this->_platform->_ordersdf['index_field'])){
            $encrypt_source_data = $this->_platform->_ordersdf['index_field'];
            
            //天猫会员加密信息
            if($this->_platform->_ordersdf['member_info'] && isset($this->_platform->_ordersdf['member_info']['buyer_open_uid'])) {
                $encrypt_source_data['buyer_open_uid'] = $this->_platform->_ordersdf['member_info']['buyer_open_uid'];
            }
            
            $this->_platform->_newOrder['encrypt_data'] = json_encode($encrypt_source_data);
        }
    }
    
    /**
     * 更新订单数据格式转换
     * 
     * @return void
     */
    public function update()
    {
        //pause
        if (in_array($this->_platform->_tgOrder['pay_status'], array('6','7')) && in_array($this->_platform->_ordersdf['pay_status'], array('1','3','4','5'))) {
            $this->_platform->_newOrder['pause'] = 'false';
        }
        
        $master = array();
        $master['pay_status']                = $this->_platform->_ordersdf['pay_status'];
        $master['discount']                  = $this->_platform->_ordersdf['discount'];
        $master['pmt_goods']                 = $this->_platform->_ordersdf['pmt_goods'];
        $master['pmt_order']                 = $this->_platform->_ordersdf['pmt_order'];
        $master['total_amount']              = $this->_platform->_ordersdf['total_amount'];
        $master['cur_amount']                = $this->_platform->_ordersdf['cur_amount'];
        $master['payed']                     = $this->_platform->_ordersdf['payed'];
        $master['cost_item']                 = $this->_platform->_ordersdf['cost_item'];
        $master['coupons_name']              = $this->_platform->_ordersdf['coupons_name'];
        $master['is_tax']                    = $this->_platform->_ordersdf['is_tax'] ? $this->_platform->_ordersdf['is_tax'] : 'false';
        $master['tax_no']                    = $this->_platform->_ordersdf['tax_no'];
        $master['cost_tax']                  = $this->_platform->_ordersdf['cost_tax'];
        $master['tax_title']                 = $this->_platform->_ordersdf['tax_title'];
        $master['weight']                    = $this->_platform->_ordersdf['weight'];
        $master['title']                     = $this->_platform->_ordersdf['title'];
        $master['score_u']                   = $this->_platform->_ordersdf['score_u'];
        $master['score_g']                   = $this->_platform->_ordersdf['score_g'];
        $master['status']                    = $this->_platform->_ordersdf['status'];
        $master['order_bool_type']           = $this->_platform->_ordersdf['order_bool_type'];
        
        //pay_bn
        $payment_cfg = $this->_platform->get_payment($this->_platform->_ordersdf['pay_bn'],$this->_platform->__channelObj->channel['node_type']);
        $master['pay_bn'] = $payment_cfg['pay_bn'];
        
        //支付单结构
        $payment_list = isset($this->_platform->_ordersdf['payments']) ? $this->_platform->_ordersdf['payments'] : array($this->_platform->_ordersdf['payment_detail']);
        if ($payment_list 
            && is_array($payment_list) 
            && $this->_platform->_ordersdf['payed'] >= $this->_platform->_tgOrder['payed']
            && in_array($this->_platform->_tgOrder['pay_status'], array('0','3','8')) ) {
            
            $last_payment = array_pop($payment_list);
            $master['paytime'] = $last_payment['pay_time'] ? kernel::single('ome_func')->date2time($last_payment['pay_time']) : time();
        }
        
        //merge
        $master = array_filter($master, array($this,'filter_null'));
        
        //删除不需要对比的数据
        unset($master['discount'], $master['is_tax'], $master['title'], $master['pay_bn']);
        
        //diff比较主数据
        $diff_master = array_udiff_assoc($master, $this->_platform->_tgOrder, array($this,'comp_array_value'));
        if ($diff_master) {
            $this->_platform->_newOrder = array_merge($this->_platform->_newOrder, $diff_master);
            
            //pause
            if (in_array($this->_platform->_newOrder['pay_status'], array('6','7'))) {
               $this->_platform->_newOrder['pause'] = 'true';
            }      
        }
        
//        $payinfo = array();
//        $payinfo['pay_name']     = $this->_platform->_ordersdf['payinfo']['pay_name'];
//        $payinfo['cost_payment'] = $this->_platform->_ordersdf['payinfo']['cost_payment'];
//        $payinfo = array_filter($payinfo,array($this,'filter_null'));
//        $diff_payinfo = array_udiff_assoc($payinfo, $this->_platform->_tgOrder['payinfo'],array($this,'comp_array_value'));
//        if ($diff_payinfo) {
//            $this->_platform->_newOrder['payinfo'] = array_merge((array)$this->_platform->_newOrder['payinfo'],$diff_payinfo);
//        }
        
//        // 天猫物流升级
//        if ($this->_platform->_ordersdf['order_type'] == 'presale' && $this->_platform->_ordersdf['cn_info']['asdp_biz_type'] == 'logistics_upgrade'){
//            $this->_platform->_newOrder['order_bool_type']           = $this->_platform->_tgOrder['order_bool_type'] | ome_order_bool_type::__CPUP_CODE;
//            $this->_platform->_newOrder['shipping']['shipping_name'] = $this->_platform->_ordersdf['shipping']['shipping_name'];
//
//            if ($this->_platform->_ordersdf['cpuperr']) {
//                $this->_platform->_newOrder['abnormal_status'] = ome_preprocess_const::__CPUPAB_CODE;
//            }
//        }
        
        //logistics_infos
        if($this->_platform->_ordersdf['logistics_infos']){
            //转换为数组(矩阵单拉订单是数组,主推订单是json字符串)
            if(is_string($this->_platform->_ordersdf['logistics_infos'])){
                $this->_platform->_ordersdf['logistics_infos'] = json_decode($this->_platform->_ordersdf['logistics_infos'], true);
            }
        }
    }
}
