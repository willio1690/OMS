<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_website_result extends erpapi_result {
    function set_response($response, $format)
    {
       parent::set_response($response, $format);
       if($this->response['rsp'] == 'error'){
           $this->response['rsp'] = 'fail';
       }
       
       if($this->response['rsp'] == 'fail'){
           $this->response['err_msg'] = $this->response['msg'];
       }
    }
}
