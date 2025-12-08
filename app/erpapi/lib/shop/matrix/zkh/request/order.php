<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 订单处理
 * Class erpapi_shop_matrix_zkh_request_order
 */
class erpapi_shop_matrix_zkh_request_order extends erpapi_shop_request_order
{
    /**
     * __forma_params_get_order_detial
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function __forma_params_get_order_detial($params)
    {
        $data['purchaseOrderId'] = $params['tid'];
        return $data;
    }
}