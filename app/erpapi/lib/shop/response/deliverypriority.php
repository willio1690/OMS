<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 订单挽单
 * Class erpapi_shop_response_deliverypriority
 */
class erpapi_shop_response_deliverypriority extends erpapi_shop_response_abstract
{
    /**
     * comeback
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function comeback($params){
        $tid = $params['tid'];
        $title = $params['fulfillmentBizType'] == '10001' ? '挽单' : '催发货';
        $this->__apilog['title']          = sprintf($title.'[%s]', $tid);
        $this->__apilog['original_bn']    = $tid;
        $this->__apilog['result']['data'] = array('tid' => $tid);
        
        if (!$tid) {
            $this->__apilog['result']['msg'] = '缺少订单号';
            return false;
        }
        
        $shop_id = $this->__channelObj->channel['shop_id'];
        
        //检查订单
        $order = $this->getOrder('order_id, order_bn, process_status, status, ship_status, order_bool_type, shop_id', $shop_id, $tid);
        
        if (!$order) {
            $this->__apilog['result']['msg'] = 'ERP不存在此单';
            return false;
        }
        
        if($order['status'] == 'dead'){
            $this->__apilog['result']['msg'] = '订单已作废';
            return false;
        }
        $order['fulfillmentBizType'] = $params['fulfillmentBizType'];
        return $order;
    }
}