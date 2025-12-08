<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 售前退款接口
 *
 * @version 2024.04.11
 */
class erpapi_dealer_response_refund extends erpapi_dealer_response_abstract
{
    /**
     * 添加
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function add($params)
    {
        $this->__apilog['title'] = '售前退款业务处理[退款单：'. $params['refund_bn'] .',店铺：'. $this->__channelObj->channel['name'] .']';
        $this->__apilog['original_bn'] = $params['order_bn'];
        $this->__apilog['result']['data'] = array('tid'=>$params['order_bn'],'refund_id'=>$params['refund_bn'],'retry'=>'false');
        
        //error_msg
        $this->__apilog['result']['msg'] = '创建退款单不走此接口';
        
        return false;
    }
    
    /**
     * statusUpdate
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function statusUpdate($params)
    {
        $this->__apilog['title'] = '更新退款单状态[退款单：'. $params['refund_bn'] .',店铺：'. $this->__channelObj->channel['name'] .']';
        $this->__apilog['original_bn'] = $params['order_bn'];
        $this->__apilog['result']['data'] = array('tid'=>$params['order_bn'],'refund_id'=>$params['refund_bn'],'retry'=>'false');
        
        //error_msg
        $this->__apilog['result']['msg'] = '更新退款单状态不走此接口';
        
        return false;
    }
}