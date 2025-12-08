<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_ticket_openapi_config extends erpapi_ticket_config
{
    /**
     * 应用级参数
     *
     * @param String $method 请求方法
     * @param Array $params 业务级请求参数
     * @return void
     * @author 
     **/
    public function get_query_params($method, $params){
        // 各自实现
        $query_params = array(
          
        );

        return $query_params;
    }

    public function gen_sign($params)
    {
        $private_key = $this->__channelObj->channel['config']['private_key'];
        $str = self::assemble($params);
        return hash_hmac('sha256', $str, $private_key);
    }

    public function get_url($method, $params, $realtime){
        $url = $this->__channelObj->channel['config']['api_url'];

        return $url;
    }
    
}
