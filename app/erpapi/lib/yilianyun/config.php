<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_yilianyun_config extends erpapi_config
{
    /**
     * 应用级参数
     * 
     * @param String $method 请求方法
     * @param Array $params 业务级请求参数
     * @return void
     * @author
     * */
    public function get_query_params($method, $params)
    {
        $params = [
            'client_id' => $params['temp']['client_id'],
            'timestamp' => time(),
            'id' => ome_func::uuid(),
        ];
        return $params;
    }

    /**
     * 获取请求地址
     * 
     * @param String $method 请求方法
     * @param Array $params 业务级请求参数
     * @param Boolean $realtime 同步|异步
     * @return void
     * @author
     * */
    public function get_url($method, $params, $realtime)
    {
        return 'https://open-api.10ss.net/v2' . $method;
    }

    /**
     * 签名
     * 
     * @param Array $params 参数
     * @return void
     * @author
     * */
    public function gen_sign($params)
    {
        return md5($params['temp']['client_id'] . $params['timestamp'] . $params['temp']['client_secret']);
    }

    /**
     * 参数格式化
     * @param $params
     * @return mixed
     */
    public function format($params)
    {
        unset($params['temp']);
        return parent::format($params);
    }

    protected $__global_whitelist = array(
        YLY_OAUTH_AUTHORIZE,
        YLY_EXPRESSPRINT_INDEX,
    );
}