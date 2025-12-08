<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class base_rpc_result{

    function __construct($response,$app_id){
        $sign = $response['sign'];
        unset($response['sign']);

        //注销多余的垃圾参数
        unset($response['_FROM_MQ_QUEUE'], $response['rpc_id'], $response['task_type'], $response['taskmgr_sign'], $response['pathinfo']);

        $this->response = $response;
		if (!$app_id || !base_shopnode::token($app_id))
			$sign_check = base_certificate::gen_sign($response);
		else
			$sign_check = base_shopnode::gen_sign($response,$app_id);
        if($sign != $sign_check){
            trigger_error('sign error!',E_USER_ERROR);
        }
    }

    function set_callback_params($params){
        $this->callback_params = $params;
    }

    function get_callback_params(){
        return $this->callback_params;
    }

    function get_pid(){
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
    
    /*
    function set_request_params($log_id){
        $paramsCacheLib = kernel::single('taoexlib_params_cache');
        $paramsCacheLib->fetch($log_id, $request_params);
        $paramsCacheLib->connClose();
        $this->request_params = unserialize($request_params);
    }*/
    
    function set_request_params($request_params){
        $this->request_params = $request_params;
    }

    function get_request_params(){
        return $this->request_params;
    }

    function set_msg_id($msg_id){
        $this->msg_id = $msg_id;
    }

    function get_msg_id(){
        return $this->msg_id;
    }

}
