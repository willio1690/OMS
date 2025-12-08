<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 订单催发货(店小蜜)
 *
 * @author wangbiao<wangbiao@shopex.cn>
 * @version 0.1
 */
class erpapi_shop_response_delivergoods extends erpapi_shop_response_abstract
{
    /**
     * 接收参数
     */

    public $_sdf = array();
    
    /**
     * 催发货
     * 
     * @param array $params
     * @return array
     */
    public function urgent($params){
        $tid = $params['tid'];

        $this->__apilog['title']          = sprintf('催发货[%s]', $tid);
        $this->__apilog['original_bn']    = $tid;
        $this->__apilog['result']['data'] = array('tid' => $tid);

        if (!$tid) {
            $this->__apilog['result']['msg'] = '缺少订单号';
            return false;
        }

        $shop_id = $this->__channelObj->channel['shop_id'];

        //检查订单
        $orderMdl = app::get('ome')->model('orders');
        $order = $this->getOrder('order_id, order_bn, process_status, status, ship_status, order_bool_type, shop_id', $shop_id, $tid);

        if (!$order) {
            $this->__apilog['result']['msg'] = 'ERP不存在此单';
            return false;
        }

        if($order['status'] == 'dead'){
            $this->__apilog['result']['msg'] = '订单已作废';
            return false;
        }

        if ($order['ship_status'] == '1') {
            $this->__apilog['result']['msg'] = '订单已经发货';
            return false;
        }
        
        $order['seller_name'] = $params['seller_name'];
        $order['logistics_time'] = $params['logistics_time'];
        return $order;
    }
    
    /**
     * 获取数据
     * 
     * @param array $params
     * @return array:
     */
    protected function _returnParams($params) {
        return array();
    }
    
    /**
     * 格式化参数
     * 
     * @param array $params
     * @return array:
     */
    protected function _formatParams($params) {
        $sdf = array('order_bn'=>$params['tid']);
        
        return $sdf;
    }

    public function promise($params)
    {
        $tid = $params['orderId'];

        $this->__apilog['title']          = sprintf('时效订单[%s]', $tid);
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
        $sdf = $this->_formatPromiseParams($params, $order);
        return $sdf;
    }

    protected function _formatPromiseParams($params, $order)
    {
        $sdf = [
            'pick_date' => $params['pickDate'],
            'delivered_time' => $params['deliveredTime'],
            'event_type' => $params['event_type'],
            'order' => $order
        ];
        return $sdf;
    }
}
