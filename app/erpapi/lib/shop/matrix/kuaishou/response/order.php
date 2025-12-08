<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2020/8/26 18:14:20
 * @describe 订单处理
 */

class erpapi_shop_matrix_kuaishou_response_order extends erpapi_shop_response_order
{

    protected $_update_accept_dead_order = true;

    protected function _analysis()
    {
        parent::_analysis();
        
        // 快手改价
        if ($this->_ordersdf['trade_type'] != 'step' 
            && $this->_ordersdf['t_type'] != 'step'
            && $this->_ordersdf['is_yushou'] != 'true'
            && preg_match('/订单实付金额从￥(\d+\.\d+)改为￥(\d+\.\d+)/', $this->_ordersdf['mark_text'],$m)){
            $newPayed = $m[2];

            $newTotal = $this->_ordersdf['extend_field']['total_fee'] + $this->_ordersdf['shipping']['cost_shipping'];

            if ($newPayed == $newTotal) {
                // 重置主结构
                $this->_ordersdf['pmt_order'] += $this->_ordersdf['total_amount'] - $newTotal;

                $this->_ordersdf['total_amount'] = $this->_ordersdf['payed'] = $newTotal;

                // 重置支付单
                $this->_ordersdf['payments'][0]['money'] = $newTotal;

                // 重置均摊实付
                foreach($this->_ordersdf['order_objects'] as $key=>$object){
                    foreach($object['order_items'] as $k=>$item){
                        $this->_ordersdf['order_objects'][$key]['order_items'][$k]['divide_order_fee'] = 0;
                        $this->_ordersdf['order_objects'][$key]['order_items'][$k]['part_mjz_discount'] = 0;
                    }

                    $this->_ordersdf['order_objects'][$key]['divide_order_fee'] = 0;
                    $this->_ordersdf['order_objects'][$key]['part_mjz_discount'] = 0;
                }
            }
        }

        if($this->_ordersdf['extend_field']){
            // 集运标识转成oms本地标识
            if ($this->_ordersdf['extend_field']['consolidate_info']) {

                $consolList = [
                    '1'  => 'XJJY', // 中国新疆中转
                ];
                $consolType = $this->_ordersdf['extend_field']['consolidate_info']['consolidate_type'];
                if ($consolList[$consolType]) {
                    $this->_ordersdf['extend_field']['consolidate_info']['consolidate_type'] = $consolList[$consolType];
                }
            }

            // 国补
            if ($gov_subsidy = $this->_ordersdf['extend_field']['gov_subsidy']) {
                $this->_ordersdf['guobu_info'] = [];
                if ($gov_subsidy['governmentDiscount']>0) {
                    $this->_ordersdf['guobu_info']['use_gov_subsidy_new'] = true;

                    if ($gov_subsidy['orderLabels'] == '21') {
                        $this->_ordersdf['guobu_info']['use_gov_subsidy_new'] = true;
                        $this->_ordersdf['guobu_info']['guobu_type'][] = 0x0010; // 国补供销
                    } elseif ($gov_subsidy['orderLabels'] == '22') {
                        $this->_ordersdf['guobu_info']['use_gov_subsidy_new'] = true;
                        $this->_ordersdf['guobu_info']['guobu_type'][] = 0x0020; // 国补自销
                    }
                }

                if ($this->_ordersdf['guobu_info']) {
                    $this->_ordersdf['guobu_info']['gov_subsidy'] = $gov_subsidy;

                    foreach ($this->_ordersdf['order_objects'] as $k => $_object) {
                        foreach ( $_object['order_items'] as  $_item ){
                            if (isset($_item['extend_item_list'])) {
                                $this->_ordersdf['guobu_info']['item_extra'][] = $_item['extend_item_list'];
                            }
                        }
                    }
                }
            }

            
        }

        // 订单达人
        $author_info = [];
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
                    if ($oiv['extend_item_list']['room_id']) {
                        if (!$this->_ordersdf['order_objects'][$ook]['addon']) {
                            $this->_ordersdf['order_objects'][$ook]['addon'] = [];
                        }
                        $this->_ordersdf['order_objects'][$ook]['addon']['room_id']  = $oiv['extend_item_list']['room_id'];
                        $author_info[$oov['oid']]['room_id'] = $oiv['extend_item_list']['room_id'];
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

        if ($this->_ordersdf['cn_info']) {
            if ($this->_ordersdf['cn_info']['promise_collect_time']) {
                $this->_ordersdf['cn_info']['promise_delivery_time']= $this->_ordersdf['cn_info']['promise_collect_time'];
                
            }
            if ($this->_ordersdf['cn_info']['promise_express_code']) {
                $this->_ordersdf['shipping']['shipping_name'] = $this->_ordersdf['cn_info']['promise_express_code'];
                $this->_ordersdf['biz_delivery_code']= $this->_ordersdf['cn_info']['promise_express_code'];
            }
        }
         //is_delivery
     
        if($this->_ordersdf['is_risk'] && in_array($this->_ordersdf['is_risk'],array('true','false')) ){
            if($this->_ordersdf['is_risk'] == 'true'){
                $this->_ordersdf['is_delivery']= 'N';
                $this->_ordersdf['is_risklabels'][]= [
                     'label_code'    =>  'SOMS_ISDELIVERY',
                     'label_value'   =>  0x0002,
                ];
            }
            if($this->_ordersdf['is_risk'] == 'false'){
                $this->_ordersdf['is_delivery']= 'Y';
                $this->_ordersdf['is_risklabels'][]= [
                     'label_code'    =>  'SOMS_ISDELIVERY',
                     'label_value'   =>  0x0002,
                     'label_action'  =>'del',
                ];
            }

        }

    }

    protected function get_update_components()
    {
        $components = array('markmemo','marktype','custommemo');

        if (($this->_ordersdf['pay_status'] != $this->_tgOrder['pay_status']) ||($this->_ordersdf['shipping']['is_cod']=='true' && $this->_ordersdf['status'] == 'dead')) {
            $refundApply = app::get('ome')->model('refund_apply')->getList('apply_id',array('order_id'=>$this->_tgOrder['order_id'],'status|noequal'=>'3'));
            // 如果没有退款申请单，以前端为主
            if (!$refundApply) {
                $components[] = 'master';
            }
        }

        $rs = app::get('ome')->model('order_extend')->getList('extend_status',array('order_id'=>$this->_tgOrder['order_id']));
        if ($rs[0]['extend_status'] != 'consignee_modified') {
            $components[] = 'consignee';
        }

        return $components;
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
        $plugins   = parent::get_update_plugins();
        $plugins[] = 'orderlabels'; // 更新快手订单可更新中转集运标签
        return $plugins;
    }

    protected function _updateAnalysis(){
        // 更新订单的时候先清理当前订单的集运标识
        $order_id = $this->_tgOrder['order_id'];
        $omsConsolidateType = kernel::single('ome_bill_label')->consolidateTypeBox;
        $labelAll = app::get('omeauto')->model('order_labels')->getList('*', ['label_code|in'=>$omsConsolidateType]);
        if ($labelAll) {
            $labelAll = array_column($labelAll, 'label_id');
            kernel::single('ome_bill_label')->delLabelFromBillId($order_id, $labelAll, 'order', $error_msg);
        }
    }

}
