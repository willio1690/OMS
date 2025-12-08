<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_result{

    public $responseHttpCode = null;
    
    function set_response($response, $format)
    {
        $response = kernel::single('erpapi_format_'.$format)->data_decode($response);

        $this->response = $response;

        return $this;
    }

    function get_msg_id(){
        return $this->response['msg_id'];
    }

    function get_status(){
        return $this->response['rsp'];
    }

    function get_data(){
        return json_decode($this->response['data'],1);
    }

    function get_result(){
        return $this->response['res'];
    }

    function get_err_msg(){
        return $this->response['err_msg'];
    }

    function get_request_params(){
        return $this->request_params;
    }

    function get_response()
    {
        return $this->response;
    }

    /**
     * 设置_response_http_code
     * @param mixed $httpCode httpCode
     * @return mixed 返回操作结果
     */
    public function set_response_http_code($httpCode)
    {
        $this->responseHttpCode = $httpCode;
    }

}
