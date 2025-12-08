<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_account_result extends erpapi_result{

    function set_response($response, $format)
    {
        $response = ['rsp'=>'succ', 'data'=>$response];

        $this->response = $response;

        return $this;
    }
}
