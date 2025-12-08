<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 格式化请求响应结果Lib类
 */
class erpapi_qimen_result extends erpapi_result
{
    /**
     * 设置_response
     *
     * @param mixed $response response
     * @param mixed $format format
     * @return mixed 返回操作结果
     */
    public function set_response($response, $format)
    {
        $format = $format ? $format : 'json';
        $response = kernel::single('erpapi_format_' . $format)->data_decode($response);
        if($response){
            if(isset($response['response'])){
                $response = $response['response'];
            }
        }
        
        // 响应结果
        $rsp = ($response['flag'] == 'success' ? 'succ' : 'fail');
        
        // data
        $orderData = [];
        if($rsp == 'succ' && isset($response['trade_orders'])){
            $orderData = $response['trade_orders'];
        }
        
        $this->response = [
            'rsp'      => $rsp,
            'res'      => ($response['code'] ? $response['code'] : $response['sub_code']),
            'err_msg'  => $response['message'],
            'err_code' => $response['code'],
            'msg_id'   => $response['request_id'],
            'data'     => json_encode($orderData, JSON_UNESCAPED_UNICODE),
        ];
        
        return $this;
    }
}