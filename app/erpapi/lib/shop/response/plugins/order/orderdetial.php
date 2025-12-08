<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 获取订单详情插件
 *
 * @author wangbiao<wangbiao@shopex.cn>
 * @version 2023.01.31
 */
class erpapi_shop_response_plugins_order_orderdetial extends erpapi_shop_response_plugins_order_abstract
{
    /**
     * convert
     * @param erpapi_shop_response_abstract $platform platform
     * @return mixed 返回值
     */

    public function convert(erpapi_shop_response_abstract $platform)
    {
        $shop_id = $platform->__channelObj->channel['shop_id'];
        $order_bn = $platform->_ordersdf['order_bn'];
        
        //request
        $orderRsp = kernel::single('erpapi_router_request')->set('shop', $shop_id)->order_get_order_detial($order_bn);
        if ($orderRsp['rsp'] != 'succ') {
            return array();
        }
    
        //订单单拉详细信息
        $tradeData = ($orderRsp['data']['trade'] ? $orderRsp['data']['trade'] : $orderRsp['data']);
        
        //翱象子订单信息
        $logisObjects = $this->_formatAoxiangObjectData($tradeData);
        
        //翱象扩展信息
        $extendSdf = $this->_formatAoxiangExtendData($tradeData);
        
        //merge
        $aoxiangSdf = array(
            'order_objects' => $logisObjects,
            'order_extend' => $extendSdf,
        );
        
        return $aoxiangSdf;
    }
    
    /**
     * 订单完成后处理
     * 
     * @param $order_id
     * @param $aoxiangSdf
     * @return bool
     */
    public function postCreate($order_id, $aoxiangSdf)
    {
        $orderObjMdl = app::get('ome')->model('order_objects');
        $orderExtendObj = app::get('ome')->model('order_extend');
        
        //check
        if(empty($aoxiangSdf['order_objects']) && empty($aoxiangSdf['order_extend'])){
            return false;
        }
        
        //order_objects
        if($aoxiangSdf['order_objects']){
            foreach($aoxiangSdf['order_objects'] as $objOid => $objVal)
            {
                $updateSdf = array(
                    'promised_collect_time' => $objVal['promise_collect_time'], //承诺-最晚揽收时间
                    'promise_outbound_time' => $objVal['promise_outbound_time'], //承诺-最晚出库时间
                    'biz_sd_type' => $objVal['biz_sd_type'], //建议仓类型
                    'store_code' => $objVal['biz_store_code'], //预选仓库编码
                    'biz_delivery_type' => $objVal['biz_delivery_type'], //择配建议
                );
                $updateSdf = array_filter($updateSdf);
                
                //update
                if($updateSdf){
                    $orderObjMdl->update($updateSdf, array('order_id'=>$order_id, 'oid'=>$objOid));
                }
            }
        }
        
        //order_extend
        $extendData = $aoxiangSdf['order_extend'];
        if($extendData){
            $extendInfo = $orderExtendObj->dump(array('order_id'=>$order_id), 'order_id,extend_field');
            if($extendInfo['extend_field']){
                $extendInfo['extend_field'] = json_decode($extendInfo['extend_field'], true);
            }
            
            //info
            $axExtendInfo = array(
                'es_time' => $extendData['es_time'], //物运时间(预计送达时间)
                'promise_service' => $extendData['promise_service'], //物流服务标签
                'promised_sign_time' => $extendData['promise_sign_time'], //承诺最晚送达时间
                'promised_collect_time' => $extendData['promise_collect_time'], //承诺最晚揽收时间
                'latest_delivery_time' => $extendData['latest_delivery_time'], //最晚出库时间
                'plan_sign_time' => $extendData['plan_sign_time'], //计划送达时间
                'push_time' => $extendData['push_time'], //ERP应推单时间(主单)
                'biz_delivery_code' => $extendData['biz_delivery_code'], //建议快递名单
                'white_delivery_cps' => $extendData['white_delivery_cps'], //快递白名单
                'black_delivery_cps' => $extendData['black_delivery_cps'], //快递黑名单
                'cpup_service' => $extendData['cpup_service'], //物流升级服务
            );
            $axExtendInfo = array_filter($axExtendInfo);
            
            //extend_field
            $extendFieldInfo = array(
                'biz_sd_type' => $extendData['biz_sd_type'], //建议仓类型
                'biz_store_code' => $extendData['biz_store_code'], //预选仓库编码
                'biz_delivery_type' => $extendData['biz_delivery_type'], //择配建议
                'send_country' => $extendData['send_country'], //国家--预估发货地址
                'send_state' => $extendData['send_state'], //省--预估发货地址
                'send_city' => $extendData['send_city'], //市
                'send_district' => $extendData['send_district'], //区
                'send_town' => $extendData['send_town'], //镇
                'send_division_code' => $extendData['send_division_code'], //预估发货地编码
            );
            $extendFieldInfo = array_filter($extendFieldInfo);
            
            //merge
            if($extendFieldInfo){
                $extendInfo['extend_field'] = array_merge($extendInfo['extend_field'], $extendFieldInfo);
            }
            
            //merge
            if($axExtendInfo){
                $extendInfo = array_merge($extendInfo, $axExtendInfo);
            }
            
            //json_encode
            $extendInfo['extend_field'] = json_encode($extendInfo['extend_field']);
            
            //order_id
            $extendInfo['order_id'] = $order_id;
            
            //update
            $orderExtendObj->save($extendInfo);
        }
        
        return false;
    }
    
    /**
     * 更新后操作
     * 
     * @param $order_id
     * @param $aoxiangSdf
     * @return bool
     */
    public function postUpdate($order_id, $ordertypesdf)
    {
        return false;
    }
    
    /**
     * 格式化翱象数据
     * 
     * @param $ordersdf
     * @return void
     */
    public function _formatAoxiangObjectData($ordersdf)
    {
        //子单纬度的择仓、择配
        $logistics_infos = $ordersdf['logistics_infos'];
        
        //check
        if(empty($logistics_infos) || !is_array($logistics_infos)) {
            return array();
        }
        
        //子单物流发货信息
        $logisObjects = array();
        foreach($logistics_infos as $key => $val)
        {
            $oid = $val['sub_trade_id'];
            
            //check
            if(empty($oid)){
                continue;
            }
            
            //format
            $val['promise_collect_time'] = ($val['promise_collect_time'] ? strtotime($val['promise_collect_time']) : 0);
            $val['promise_outbound_time'] = ($val['promise_outbound_time'] ? strtotime($val['promise_outbound_time']) : 0);
            
            //item
            $logisObjects[$oid] = $val;
        }
        
        return $logisObjects;
    }
    
    /**
     * 翱象数据格式化
     * 
     * @param $ordersdf
     * @return void
     */
    public function _formatAoxiangExtendData($ordersdf)
    {
        $extendSdf = array();
        
        //翱象物流协议
        $cnInfo = $ordersdf['cn_info'];
        
        //子单纬度的择仓、择配
        $logistics_infos = $ordersdf['logistics_infos'];
        
        //check
        if(empty($cnInfo) && empty($logistics_infos)){
            return array();
        }
        
        //预计送达时间
        if($cnInfo['es_date']){
            $extendSdf['es_time'] = strtotime($cnInfo['es_date'] .' 00:00:00');
        }
        
        //物流服务
        if($cnInfo['logistics_agreement']){
            //物流服务标签
            if($cnInfo['logistics_agreement']['logistics_service_msg']){
                $extendSdf['promise_service'] = $cnInfo['logistics_agreement']['logistics_service_msg'];
            }
            
            //计划送达时间
            if($cnInfo['logistics_agreement']['sign_time']){
                $extendSdf['plan_sign_time'] = strtotime($cnInfo['logistics_agreement']['sign_time']);
            }
            
            //承诺/最晚送达时间
            if($cnInfo['logistics_agreement']['promise_sign_time']){
                $extendSdf['promise_sign_time'] = strtotime($cnInfo['logistics_agreement']['promise_sign_time']);
            }
            
            //ERP应推单时间(主单)
            if($cnInfo['logistics_agreement']['push_time']){
                $extendSdf['push_time'] = strtotime($cnInfo['logistics_agreement']['push_time']);
            }
            
            //物流升级服务
            if($cnInfo['logistics_agreement']['asdp_ads']){
                $extendSdf['cpup_service'] = $cnInfo['logistics_agreement']['asdp_ads'];
            }
        }
        
        //子单物流发货信息
        if($logistics_infos && is_array($logistics_infos)) {
            $logisticsInfos = array();
            foreach($logistics_infos as $key => $val)
            {
                //format
                $val['promise_collect_time'] = ($val['promise_collect_time'] ? strtotime($val['promise_collect_time']) : 0);
                $val['promise_outbound_time'] = ($val['promise_outbound_time'] ? strtotime($val['promise_outbound_time']) : 0);
                
                //建议使用快递名单
                if($val['biz_delivery_code']){
                    $tempCps = explode(',', $val['biz_delivery_code']);
                    $tempCps = array_unique(array_filter($tempCps));
                    
                    //merge
                    if($logisticsInfos['biz_delivery_codes']){
                        $logisticsInfos['biz_delivery_codes'] = array_merge($logisticsInfos['biz_delivery_codes'], $tempCps);
                    }else{
                        $logisticsInfos['biz_delivery_codes'] = $tempCps;
                    }
                }
                
                //快递白名单
                if($val['white_delivery_cps']){
                    $tempCps = explode(',', $val['white_delivery_cps']);
                    $tempCps = array_unique(array_filter($tempCps));
                    
                    //merge
                    if($logisticsInfos['white_delivery_cps']){
                        $logisticsInfos['white_delivery_cps'] = array_merge($logisticsInfos['white_delivery_cps'], $tempCps);
                    }else{
                        $logisticsInfos['white_delivery_cps'] = $tempCps;
                    }
                }
                
                //快递黑名单
                if($val['black_delivery_cps']){
                    $tempCps = explode(',', $val['black_delivery_cps']);
                    $tempCps = array_unique(array_filter($tempCps));
                    
                    //merge
                    if($logisticsInfos['black_delivery_cps']){
                        $logisticsInfos['black_delivery_cps'] = array_merge($logisticsInfos['black_delivery_cps'], $tempCps);
                    }else{
                        $logisticsInfos['black_delivery_cps'] = $tempCps;
                    }
                }
                
                //子单的【最晚揽收时间】
                if($val['promise_collect_time']){
                    $logisticsInfos['promise_collect_time'][] = $val['promise_collect_time'];
                }
                
                //子单的【最晚出库时间】
                if($val['promise_outbound_time']){
                    $logisticsInfos['promise_outbound_time'][] = $val['promise_outbound_time'];
                }
                
                //预估发货地址
                if($val['send_state'] && $val['send_city']){
                    $extendSdf['send_country'] = $val['send_country']; //国家
                    $extendSdf['send_state'] = $val['send_state']; //省
                    $extendSdf['send_city'] = $val['send_city']; //市
                    $extendSdf['send_district'] = $val['send_district']; //区
                    $extendSdf['send_town'] = $val['send_town']; //镇
                    $extendSdf['send_division_code'] = $val['send_division_code']; //预估发货地编码
                }
                
                //建议仓类型
                if($val['biz_sd_type']){
                    $extendSdf['biz_sd_type'] = $val['biz_sd_type'];
                }
                
                //预选仓库编码
                if($val['biz_store_code'] && in_array($extendSdf['biz_sd_type'], array(1, 2))){
                    $extendSdf['biz_store_code'] = $val['biz_store_code'];
                }
                
                //择配建议
                if($val['biz_delivery_type']){
                    $extendSdf['biz_delivery_type'] = $val['biz_delivery_type'];
                }
            }
            
            //format物流公司列表唯一性
            if($logisticsInfos['biz_delivery_codes'] && is_array($logisticsInfos['biz_delivery_codes'])){
                $tempList = array();
                foreach ($logisticsInfos['biz_delivery_codes'] as $logi_key => $logi_code)
                {
                    $tempList[$logi_code] = $logi_code;
                }
                
                $logisticsInfos['biz_delivery_codes'] = $tempList;
            }
            
            if($logisticsInfos['white_delivery_cps'] && is_array($logisticsInfos['white_delivery_cps'])){
                $tempList = array();
                foreach ($logisticsInfos['white_delivery_cps'] as $logi_key => $logi_code)
                {
                    $tempList[$logi_code] = $logi_code;
                }
                
                $logisticsInfos['white_delivery_cps'] = $tempList;
            }
            
            if($logisticsInfos['black_delivery_cps'] && is_array($logisticsInfos['black_delivery_cps'])){
                $tempList = array();
                foreach ($logisticsInfos['black_delivery_cps'] as $logi_key => $logi_code)
                {
                    $tempList[$logi_code] = $logi_code;
                }
                
                $logisticsInfos['black_delivery_cps'] = $tempList;
            }
            
            //json
            $extendSdf['biz_delivery_code'] = ($logisticsInfos['biz_delivery_codes'] ? json_encode($logisticsInfos['biz_delivery_codes']) : '');
            $extendSdf['white_delivery_cps'] = ($logisticsInfos['white_delivery_cps'] ? json_encode($logisticsInfos['white_delivery_cps']) : '');
            $extendSdf['black_delivery_cps'] = ($logisticsInfos['black_delivery_cps'] ? json_encode($logisticsInfos['black_delivery_cps']) : '');
            
            //取子单上最早的【最晚揽收时间】
            if($logisticsInfos['promise_collect_time']){
                $extendSdf['promise_collect_time'] = min($logisticsInfos['promise_collect_time']);
            }
            
            //取子单上最早的【最晚出库时间】
            if($logisticsInfos['promise_outbound_time']){
                $extendSdf['latest_delivery_time'] = min($logisticsInfos['promise_outbound_time']);
            }
        }
        
        return $extendSdf;
    }
}
