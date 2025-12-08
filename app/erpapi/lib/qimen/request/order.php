<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_qimen_request_order extends erpapi_qimen_request_abstract
{
    /**
     * 获取店铺订单详情
     * 
     * @param String $order_bn 订单号
     * @return void
     * @author
     * */
    public function get_order_detial($order_bn)
    {
        // title
        $title = "店铺(". $this->__channelObj->channel['name'] .")获取前端店铺". $order_bn ."的订单详情";
        
        // format params
        $params = $this->__forma_params_get_order_detial($order_bn);
        
        // order_type
        $order_type = ($this->__channelObj->channel['business_type'] == 'zx') ? 'direct' : 'agent';
        
        // api_name
        $api_name = 'qimen.taobao.erp.order.sync';
        
        // request
        $rsp = $this->__caller->call($api_name, $params, array(), $title, 10, $order_bn);
        
        // result
        $result = array();
        $result['rsp']        = $rsp['rsp'];
        $result['err_msg']    = $rsp['err_msg'];
        $result['msg_id']     = $rsp['msg_id'];
        $result['res']        = $rsp['res'];
        $result['data']       = json_decode($rsp['data'],1);
        $result['order_type'] = $order_type;
        
        return $result;
    }
    
    /**
     *  格式化请求参数
     *
     * @param $params
     * @return mixed
     */
    public function __forma_params_get_order_detial($order_bn)
    {
        // extendProps
        $extendProps = [
            'from_node_id' => base_shopnode::node_id('ome'), // OMS系统节点
            'to_node_id' => $this->__channelObj->channel['node_id'], // OMS店铺节点
            'tid' => $order_bn, // 订单号
        ];
        
        // params
        $params = [
            'page_no' => 1,
            'pageSize' => 1,
            'customerId' => $this->__channelObj->channel['app_key'],
            'extendProps' => json_encode($extendProps, JSON_UNESCAPED_UNICODE),
        ];
        
        return $params;
    }
    
    /**
     * 格式化请求参数
     *
     * @param array $params
     * @return array
     */
    public function format_add_params($params)
    {
        //请求参数
        $requestParams = $this->get_request_params($params);
        
        return $requestParams;
    }
}