<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_openapi_dms_config extends erpapi_store_openapi_config
{

    /**
     * gen_sign
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function gen_sign($params)
    {
        $private_key = $this->__channelObj->store['config']['private_key'];

        $str = self::assemble($params);

        return hash_hmac('sha256', $str, $private_key);
    }

    /**
     * 获取_url
     * @param mixed $method method
     * @param mixed $params 参数
     * @param mixed $realtime realtime
     * @return mixed 返回结果
     */
    public function get_url($method, $params, $realtime)
    {
        $url = $this->__channelObj->store['config']['api_url'];

        return $url;
    }

    /**
     * format
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function format($params)
    {
        return json_encode($params, JSON_UNESCAPED_UNICODE);
    }
}
