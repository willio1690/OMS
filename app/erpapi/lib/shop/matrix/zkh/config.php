<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_zkh_config extends erpapi_shop_config
{
    /**
     * 获取请求地址
     * @param String $method
     * @param array $params
     * @param bool $realtime
     * @return string|void
     * @author db
     * @date 2023-10-10 6:26 下午
     */
    public function get_url($method, $params, $realtime)
    {
        $url = MATRIX_GO_URL;
        if ($realtime == true) {
            $url .= 'sync';
        } else {
            $url .= 'async';
        }
        return $url;
    }
}