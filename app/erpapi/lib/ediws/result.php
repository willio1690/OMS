<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 格式化请求响应结果Lib类
 */
class erpapi_ediws_result extends erpapi_result
{
    /**
     * 设置_response
     * @param mixed $response response
     * @param mixed $format format
     * @return mixed 返回操作结果
     */

    public function set_response($response, $format)
    {

        $response = kernel::single('erpapi_format_' . $format)->data_decode($response);


        $messageCode = trim($response['success']);
        
        if($messageCode == true){
            $rsp = 'succ';
        }else{
            $rsp = 'fail';
        }

        if($rsp=='fail' && $response['code']==200 && $response['data']){
            $rsp = 'succ';
        }
        $response['message'] = ($response['Message'] ? $response['Message'] : $response['message']);
        $this->response = [
            'rsp'     => $rsp,
            'data'    => $response,
            'res'     => '',
            'err_msg' => $response['message'],
            'err_code'=>$response['responseCode'],
        ];

      
        return $this;
    }
}