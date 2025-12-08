<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2016/12/15
 * @describe 订单处理
 */

class erpapi_shop_matrix_pinduoduo_response_order extends erpapi_shop_response_order
{
    //平台订单状态
    protected $_sourceStatus = array(
        '1'  => 'WAIT_SELLER_SEND_GOODS', //待发货
        '2'  => 'WAIT_BUYER_CONFIRM_GOODS', //已发货待签收
        '3'  => 'TRADE_FINISHED', //已签收
    );
    
    protected $_update_accept_dead_order = true;

    public function _securityHashCode(){
        $this->_ordersdf['member_info']['buyer_open_uid'] = $this->_ordersdf['index_field']['open_address_id'];
        parent::_securityHashCode();
    }

    protected function _analysis()
    {
        parent::_analysis();

        if($this->_ordersdf['consignee']['area_city'] == '县') {
            $this->_ordersdf['consignee']['area_city'] = $this->_ordersdf['consignee']['area_state'];
        }

        // 拼多多顺丰加价订单强制发顺丰
        if (0 === strpos($this->_ordersdf['mark_text'],'顺丰加价;')) {
            $this->_ordersdf['shipping']['shipping_name'] = '顺丰';
        }
        
        // 拼多多集运标识
        if ($this->_ordersdf['extend_field']['consolidate_info']) {
            $this->_ordersdf['consolidate_type'] = $this->_ordersdf['extend_field']['consolidate_info']['consolidate_type'];

            // 集运标识转成oms本地标识
            $consolList = [
                '0'  => 'XGJY', // 中国香港集运
                '1'  => 'XJJY', // 中国新疆中转
                '2'  => 'HSKSTJY', // 哈萨克斯坦集运
                '3'  => 'XZJY', // 中国西藏中转
                '5'  => 'RBJY', // 日本集运
                '6'  => 'TWJY', // 中国台湾集运
                '7'  => 'HGJY', // 韩国集运
                '8'  => 'XJPJY', // 新加坡集运
                '9'  => 'MLXYJY', // 马来西亚集运
                '10' => 'TGJY', // 泰国集运
            ];
            $consolType = $this->_ordersdf['extend_field']['consolidate_info']['consolidate_type'];
            if ($consolList[$consolType]) {
                $this->_ordersdf['extend_field']['consolidate_info']['consolidate_type'] = $consolList[$consolType];
            }
        }
        $this->_ordersdf['is_delivery']= 'Y';
     
        if ($this->_ordersdf['extend_field']) {
            // 国补
            if ($gov_subsidy = $this->_ordersdf['extend_field']['gov_subsidy']) {
                $this->_ordersdf['guobu_info'] = [];

                if ($gov_subsidy['trade_in_national_subsidy_amount_type'] == '1') {

                    $this->_ordersdf['guobu_info']['use_gov_subsidy_new'] = true;
                    $this->_ordersdf['guobu_info']['guobu_type'][] = 1; // 支付立减
                    $this->_ordersdf['guobu_info']['gov_subsidy_amount_new'] = $gov_subsidy['trade_in_national_subsidy_amount'];

                } elseif ($gov_subsidy['trade_in_national_subsidy_amount_type'] == '2') {

                    $this->_ordersdf['guobu_info']['use_gov_subsidy_new'] = true;
                    $this->_ordersdf['guobu_info']['guobu_type'][] = 2; // 下单立减
                    $this->_ordersdf['guobu_info']['gov_subsidy_amount_new'] = $gov_subsidy['trade_in_national_subsidy_amount'];
                }

                if ($this->_ordersdf['guobu_info']) {
                    $this->_ordersdf['guobu_info']['order_tag_list'] = $this->_ordersdf['extend_field']['order_tag_list'];
                }
            }

            
            if($this->_ordersdf['extend_field']['order_tag_list']){

                foreach($this->_ordersdf['extend_field']['order_tag_list'] as $v){
                    if($v['name']=='bought_from_vegetable' && $v['value']==1){

                        $this->_ordersdf['order_bool_type'] = ome_order_bool_type::__CPUP_CODE;
                        $this->_ordersdf['cpup_service'] = '204';
                    }
                    if($v['name']=='promise_delivery' && $v['value']==1){
                        $this->_ordersdf['logictics_labels'][]=
                        ['label_code'=>'SOMS_LOGISTICS','label_value'=>0x0004];
                    }
                }

                
            }

           if(isset($this->_ordersdf['extend_field']['gift_order_status']) && in_array($this->_ordersdf['extend_field']['gift_order_status'],['0','1','2'])){

                if($this->_ordersdf['extend_field']['gift_order_status']==0){
  

                    $this->_ordersdf['is_delivery']= 'N';
                }

               
           }



        }


        //is_delivery
        if($this->_ordersdf['is_risk'] && in_array($this->_ordersdf['is_risk'],array('true','false')) ){
            if($this->_ordersdf['is_risk'] == 'true'){
                $this->_ordersdf['is_delivery']= 'N';
            }
            

        }

        // 拼多多平台优惠金额
        if ($this->_ordersdf['platform_discount']) {
            $this->_ordersdf['platform_cost_amount'] = $this->_ordersdf['platform_discount'];
        }

    }

    protected function get_update_plugins()
    {
        $plugins = parent::get_update_plugins();

        //判断如果是已完成只更新时间
        if ($this->_ordersdf['status'] == 'finish' && $this->_ordersdf['end_time']>0){
            $plugins = array();
            $plugins[] = 'confirmreceipt';
        }
        
         if($this->_ordersdf['is_delivery']=='Y'){
            $plugins[] = 'orderlabels';
            
        }

        return $plugins;
    }

    protected function get_update_components()
    {
        $components = array('markmemo','marktype','custommemo');
        if (($this->_ordersdf['pay_status'] != $this->_tgOrder['pay_status']) ||($this->_ordersdf['shipping']['is_cod']=='true' && $this->_ordersdf['status'] == 'dead')) {
        	$refundApply = app::get('ome')->model('refund_apply')->getList('apply_id',array('order_id'=>$this->_tgOrder['order_id'],'status|noequal'=>'3'));
        	if (!$refundApply) {

            	$components[] = 'master';
        	}
        }
        if($this->_tgOrder['order_bool_type'] & ome_order_bool_type::__RISK_CODE) {
            $components[] = 'member';
        }
        if($this->_tgOrder && $this->_ordersdf['consignee']['name']){
            $rs = app::get('ome')->model('order_extend')->getList('extend_status',array('order_id'=>$this->_tgOrder['order_id']));
            // 如果ERP收货人信息未发生变动时，则更新拼多多收货人信息
            if ($rs[0]['extend_status'] != 'consignee_modified') {
                // $components[] = 'consignee';
                $orRe = app::get('ome')->model('order_receiver')->db_dump(['order_id'=>$this->_tgOrder['order_id']], 'encrypt_source_data');
                $ensd = json_decode($orRe['encrypt_source_data'], 1);
                if(empty($ensd['open_address_id']) || $ensd['open_address_id'] != $this->_ordersdf['index_field']['open_address_id'] || !$this->_tgOrder['consignee']['name']) {
                    $components[] = 'consignee';
                }
            }
        }
    
        if($this->_ordersdf['is_delivery'] == 'Y' && $this->_tgOrder['is_delivery']=='N'){
            $components[] = 'master';

        }

        if($this->_tgOrder['status']=='finish'){
            $components = [];
        }
        return $components;
    }
    
    /**
     * 是否接收订单
     *
     * @return void
     * @author
     **/
    protected function _canAccept()
    {
        if (isset($this->_ordersdf['extend_field']['group_status']) && $this->_ordersdf['extend_field']['group_status'] != '1') {
//            $this->__apilog['result']['msg'] = '未拼团成功不接收';
//            return false;
        }
        
        return parent::_canAccept();
    }


    protected function _canCreate()
    {
        $res = parent::_canCreate();
        if (!$res) {
            if ('1'!=app::get('ome')->getConf('ome.get.all.status.order')){
                return $res;
            } else {
                if ($this->__apilog['result']['msg'] == '取消订单不接收') {
                    return true;
                }
                return $res;
            }
        }
    }


    //创建订单的插件
    protected function get_create_plugins()
    {
        $plugins = parent::get_create_plugins();

        $plugins[] = 'orderlabels';
        $plugins[] = 'coupon';
        return $plugins;
    }

    protected function _operationSel()
    {
        parent::_operationSel();

        if($this->_operationSel == 'update'){
            if ($this->_ordersdf['status'] == 'dead' && $this->_tgOrder['status']=='active' &&  $this->_ordersdf['pay_status']=='5' && $this->_tgOrder['pay_status']=='4' && $this->_ordersdf['ship_status']=='0'){
                $this->_operationSel = 'close';
            }
        }
    }
}
