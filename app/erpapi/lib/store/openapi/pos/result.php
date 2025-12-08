<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_openapi_pos_result extends erpapi_result {
    function set_response($response, $format)
    {
       

        $response = kernel::single('erpapi_format_'.$format)->data_decode($response);
        $result = $response['result'];
       
        $this->response = $result;

        return $this;
    }
    
}
