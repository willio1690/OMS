<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_request_paymenthod extends erpapi_shop_request_abstract
{
    /**
     * 获取paymethod
     * @return mixed 返回结果
     */
    public function getpaymethod(){}

    /**
     * 获取_paymethod_callback
     * @param mixed $response response
     * @param mixed $callback_params 参数
     * @return mixed 返回结果
     */
    public function get_paymethod_callback($response, $callback_params){}    
}