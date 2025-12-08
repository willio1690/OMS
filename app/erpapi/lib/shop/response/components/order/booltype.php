<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0 
 * @DateTime: 2021/5/17 15:35:50
 * @describe: 订单二进制类别
 * ============================
 */

class erpapi_shop_response_components_order_booltype extends erpapi_shop_response_components_order_abstract
{
    const _APP_NAME = 'ome';
    /**
     * 数据转换
     *
     * @return void
     * @author 
     **/
    public function convert()
    {   
        
        if ($this->_platform->_ordersdf['order_bool_type']){
            $orderBoolType = $this->_platform->_ordersdf['order_bool_type'];
        }else{
            $orderBoolType = 0;
        }
        if(!empty($this->_platform->_ordersdf['cnAuto'])){
            $this->_platform->_newOrder['cnAuto']           = $this->_platform->_ordersdf['cnAuto'];
            $this->_platform->_newOrder['order_bool_type'] = $this->_platform->_newOrder['order_bool_type'] | ome_order_bool_type::__CNAUTO_CODE;
            $orderBoolType = $orderBoolType | ome_order_bool_type::__CNAUTO_CODE;
            
        }

        // 风控订单
        if ($this->_platform->_ordersdf['is_risk'] == 'true') {
            $orderBoolType = $orderBoolType | ome_order_bool_type::__RISK_CODE;
        }
        // 跨境订单ORDER_TYPE 天猫国际
        if ($this->_platform->_ordersdf['trade_type'] == 'tmall_i18n' || $this->_platform->_ordersdf['t_type'] == 'tmall_i18n') {
            $this->_platform->_newOrder['t_type']           = $this->_platform->_ordersdf['t_type'];
            
            $orderBoolType = $orderBoolType | ome_order_bool_type::__INTERNAL_CODE;
        }

        if($this->_platform->_ordersdf['extend_field']['dy_added_service']){
            $dy_added_service = $this->_platform->_ordersdf['extend_field']['dy_added_service'];
            $dy_added_service = explode(',',$dy_added_service);
            if(in_array('sug_home_deliver',$dy_added_service)){
                $orderBoolType = $orderBoolType | ome_order_bool_type::__CPUP_CODE;
                $this->_platform->_ordersdf['cpup_service'] = $this->_platform->_ordersdf['extend_field']['dy_added_service'];
            }

        }

        if ($this->_platform->_ordersdf['cn_info']) {
            if ($this->_platform->_ordersdf['cn_info']['cn_service']) {
                if($this->_platform->_ordersdf['cn_info']['3pl_timing'] == 'true') {
                    $cnBool = ome_order_bool_type::__3PL_CODE;
                } elseif($this->_platform->_ordersdf['cn_info']['push_time']) {
                    $cnBool = ome_order_bool_type::__4PL_CODE;
                } elseif($this->_platform->_ordersdf['cn_info']['timing_promise']
                    && $this->_platform->_ordersdf['cn_info']['promise_service']) {
                    $cnBool = ome_order_bool_type::__SHI_CODE;
                } else {
                    $cnBool = 0;
                }
                $orderBoolType = $orderBoolType | $cnBool;
                $cnCode = kernel::single('ome_order_func')->cnServiceToBool($this->_platform->_ordersdf['cn_info']['cn_service']);
                $this->_platform->_ordersdf['cn_service'] = $cnCode;
                $this->_platform->_ordersdf['promise_service'] = (string)$this->_platform->_ordersdf['cn_info']['promise_service'];
            }
    
            // 天猫物流升级
            if ($this->_platform->_ordersdf['cn_info']['asdp_biz_type'] == 'logistics_upgrade'){
                $orderBoolType = $orderBoolType | ome_order_bool_type::__CPUP_CODE;
        
                $this->_platform->_ordersdf['cpup_service'] = $this->_platform->_ordersdf['cn_info']['asdp_ads'];
        
                if ($this->_platform->_ordersdf['cpuperr']) {
                    $this->_platform->_newOrder['abnormal_status'] = ome_preprocess_const::__CPUPAB_CODE;
                }
            }elseif($this->_platform->_ordersdf['cn_info']['asdp_biz_type'] == 'aox' || $this->_platform->_ordersdf['cn_info']['logistics_agreement']['asdp_biz_type'] == 'aox'){
                //翱象订单
                $orderBoolType = $orderBoolType | ome_order_bool_type::__AOXIANG_CODE;
                
                //物流服务标签
                if($this->_platform->_ordersdf['logistics_service_msg']){
                    $this->_platform->_newOrder['promise_service'] = $this->_platform->_ordersdf['logistics_service_msg'];
                }elseif($this->_platform->_ordersdf['cn_info']['logistics_agreement']['logistics_service_msg']){
                    $this->_platform->_newOrder['promise_service'] = $this->_platform->_ordersdf['cn_info']['logistics_agreement']['logistics_service_msg'];
                }
            }
            
            if ($this->_platform->_ordersdf['cn_info']['delivery_time']) {
                $this->_platform->_ordersdf['latest_delivery_time'] = strtotime($this->_platform->_ordersdf['cn_info']['delivery_time']);
            }
            $this->_platform->_ordersdf['push_time'] = (int)strtotime($this->_platform->_ordersdf['cn_info']['push_time']);
            $this->_platform->_ordersdf['collect_time'] = (int)strtotime($this->_platform->_ordersdf['cn_info']['collect_time']);
            $this->_platform->_ordersdf['es_time'] = (int) $this->_platform->_ordersdf['cn_info']['es_time'];
            $this->_platform->_ordersdf['promised_collect_time'] = strtotime($this->_platform->_ordersdf['cn_info']['promise_collect_time']);
            $this->_platform->_ordersdf['promised_sign_time'] = strtotime($this->_platform->_ordersdf['cn_info']['promise_sign_time']);
        }

        // 代销订单
        if (strtolower($this->_platform->_ordersdf['is_daixiao']) == 'true') {
            $orderBoolType = $orderBoolType | ome_order_bool_type::__DAIXIAO_CODE;
        }
    
        //挽单优先发货
        if (isset($this->_platform->_ordersdf['extend_field']['priorityDelivery']) && $this->_platform->_ordersdf['extend_field']['priorityDelivery'] == true) {
            $orderBoolType = $orderBoolType | ome_order_bool_type::__COME_BACK;
        }

        //DEWU急速现货
        if ($this->_platform->_ordersdf['order_type'] == '7') {
            $this->_platform->_newOrder['order_type'] = 'jisuxianhuo';
            $orderBoolType = $orderBoolType | ome_order_bool_type::__DEWU_JISU_CODE;
        }

        //DEWU品牌直发
        if ($this->_platform->_ordersdf['order_type'] == '26') {
            $this->_platform->_newOrder['order_type'] = 'pinpaizhifa';
            $orderBoolType = $orderBoolType | ome_order_bool_type::__DEWU_BRAND_CODE;
        }

    
        //顺手买一件活动打标 加 条件 只有天猫平台打标
        if ($this->_platform->_ordersdf['order_source'] == 'taobao' && $this->_platform->_ordersdf['pmt_detail']) {
            $pmt_detail = $this->_platform->_ordersdf['pmt_detail'];
            foreach ($pmt_detail as $key => $value) {
                if (strpos($value['pmt_describe'], '顺手买一件活动') !== false) {
                    $orderBoolType = $orderBoolType | ome_order_bool_type::__ACTIVITY_PURCHASE;
                }
            }
        }
        //京东厂直 预约发货  预计送达时间和预约发货时间
        if ($this->_platform->_ordersdf['cn_info']['es_date'] || $this->_platform->_ordersdf['cn_info']['appointment_ship_time']) {
            $orderBoolType = $orderBoolType | ome_order_bool_type::__BOOKING_DELIVERY;
        }
    
        $this->_platform->_newOrder['order_bool_type'] = $orderBoolType;
    }

    /**
     * 更新发货人
     *
     * @return void
     * @author 
     **/
    public function update()
    {
       $orderBoolType = $this->_platform->_tgOrder['order_bool_type'];
       // 风控订单
       if ($this->_platform->_ordersdf['is_risk'] == 'true') {
           $orderBoolType = $orderBoolType | ome_order_bool_type::__RISK_CODE;
       }else{
           $orderBoolType = $orderBoolType & (~ome_order_bool_type::__RISK_CODE);
       }
       $this->_platform->_newOrder['order_bool_type'] = $orderBoolType;
    }
}
