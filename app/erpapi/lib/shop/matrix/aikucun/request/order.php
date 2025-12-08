<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Created by PhpStorm.
 * User: qiudi
 * Date: 18/10/10
 * Time: 上午10:50
 */
class erpapi_shop_matrix_aikucun_request_order extends erpapi_shop_request_order
{
    /**
     * __forma_params_get_order_detial
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function __forma_params_get_order_detial($params)
    {
        $params['version'] = '2.0';
        return $params;
    }
}