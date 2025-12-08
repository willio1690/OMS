<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 京东工业品
 * Class erpapi_shop_response_plugins_order_jdgxd
 */
class erpapi_shop_response_plugins_order_jdgxd extends erpapi_shop_response_plugins_order_abstract
{
    /**
     * convert
     * @param erpapi_shop_response_abstract $platform platform
     * @return mixed 返回值
     */

    public function convert(erpapi_shop_response_abstract $platform)
    {
        $gxdSdf                   = [];
        $gxdSdf['order_bn']       = $platform->_ordersdf['order_bn'];
        $gxdSdf['shop_id']        = $platform->__channelObj->channel['shop_id'];
        $gxdSdf['shippingMethod'] = 0;//发货方式 1：平台结算 2：自行结算 0：平台+自行结算 不传默认1
        $gxdSdf['is_jdgxd']       = false;
    
        //工小达
        if(isset($platform->_ordersdf['extend_field']['sendpayMap']) && is_array($platform->_ordersdf['extend_field']['sendpayMap'])) {
            foreach($platform->_ordersdf['extend_field']['sendpayMap'] as $spVal){
                if(is_string($spVal)) {
                    $spVal = json_decode($spVal, 1);
                }
                
                if (is_array($spVal) && isset($spVal['810']) && $spVal['810'] == '1') {
                    $gxdSdf['is_jdgxd']      = true;
                }
            }
        }
        
        return $gxdSdf;
    }
    
    /**
     * 平台承运商履约信息查询
     * @param $order_id
     * @param $gxdSdf
     * @date 2025-02-26 4:02 下午
     */
    public function postCreate($order_id, $gxdSdf)
    {
        //是否工小达订单
        if ($gxdSdf['is_jdgxd']) {
            kernel::single('ome_event_trigger_shop_logistics')->getCarrierPlatform($order_id);
        }
    }
    
    /**
     *
     * @param Array
     * @return void
     * @author
     **/
    public function postUpdate($order_id, $gxdSdf)
    {
    }
}