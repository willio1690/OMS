<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 失败订单异常信息记录
 *
 * @author wangbiao@shopex.cn
 * @version 2024.12.26
 */
class erpapi_shop_response_plugins_order_abnormal extends erpapi_shop_response_plugins_order_abstract
{
    /**
     * convert
     * @param erpapi_shop_response_abstract $platform platform
     * @return mixed 返回值
     */

    public function convert(erpapi_shop_response_abstract $platform)
    {
        //检查不是失败订单,直接返回
        //@todo：不能使用$platform->_ordersdf获取订单信息;
        if($platform->_newOrder['is_fail'] != 'true' || empty($platform->_newOrder['abnormal_msg'])){
            return [];
        }
        
        //order_objects
        $is_lucky_fail = false;
        foreach ($platform->_newOrder['order_objects'] as $objKey => $objVal)
        {
            //check lucky bag
            if($objVal['obj_type'] == 'lkb' && empty($objVal['order_items'])){
                $is_lucky_fail = true;
            }
        }
        
        //data
        $abnormalInfo = [
            'abnormal_type' => 'object_fail', //异常类型：订单object层异常
            'abnormal_msg' => $platform->_newOrder['abnormal_msg'], //异常信息
            'is_lucky_fail' => $is_lucky_fail,
        ];
        
        return $abnormalInfo;
    }
    
    /**
     * 订单创建完成后进行处理
     * 
     * @param int $order_id
     * @param text $abnormalInfo 异常信息
     * @return void
     */
    public function postCreate($order_id, $abnormalInfo)
    {
        //check
        if(empty($abnormalInfo)){
            return false;
        }
        
        //福袋商品失败
        if($abnormalInfo['is_lucky_fail']){
            //订单信息
            $orderInfo = $this->getOrder($order_id);
            
            //异常状态标识位
            $abnormal_status = $orderInfo['abnormal_status'] | ome_preprocess_const::__ORDER_LUCKY_FAIL;
            
            //更新订单异常状态
            app::get('ome')->model('orders')->update(array('abnormal_status'=>$abnormal_status), array('order_id'=>$order_id));
            
            //unset
            unset($abnormalInfo['is_lucky_fail']);
        }
        
        //data
        $abnormalInfo['order_id'] = $order_id;
        
        //insert
        app::get('ome')->model('order_abnormal')->insert($abnormalInfo);
        
        return true;
    }
}