<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_website_d1m_result extends erpapi_result {
    
    protected $_retryErrorMsgList = [
        'Unable to authenticate user.',
    ];
    
    function set_response($response, $format)
    {
        parent::set_response($response, $format);
        if($this->response['rsp'] == 'error'){
            $this->response['rsp'] = 'fail';
        }
        if($this->response['res'] == 'succ'){
            $this->response['rsp'] = 'succ';
        }
        
        if($this->response['rsp'] == 'fail'){
            $this->response['err_msg'] = $this->response['msg'];
        }
    
        if ($this->response['data'] && $this->response['data']['res'] == 'error') {
            $this->response['rsp']     = 'fail';
            $this->response['err_msg'] = $this->response['data']['error_message'];
        }
    
        if ($this->response['data'] && $this->response['data']['res'] == 'succ') {
            $this->response['rsp']     = 'succ';
        }
        
        if($this->response['data'] && $this->response['data']['status'] == 200){
            $this->response['rsp'] = 'succ';
        }
    }
    
    /**
     * token失效标识
     * @return string[]
     * @author db
     * @date 2023-05-22 6:35 下午
     */
    function retryErrorMsgList()
    {
        return $this->_retryErrorMsgList;
    }
}
