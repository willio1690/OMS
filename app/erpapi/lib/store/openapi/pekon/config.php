<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_openapi_pekon_config extends erpapi_store_openapi_config
{
    public $is_handle_http_code = true;
    
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
        if($method == 'get_token'){
            $url.='/login';
        }else{
            $url.='/gateway/token/v1/request';
        }
        return $url;
    }

    /**
     * format
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function format($params)
    {
        unset($params['sign']);
        return json_encode($params, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取_query_params
     * @param mixed $method method
     * @param mixed $params 参数
     * @return mixed 返回结果
     */
    public function get_query_params($method, $params)
    {
        
        if($method =='get_token'){
            $query_params = [
                'username'          => defined('PEKON_USER') ? constant('PEKON_USER') : '',
                'password'          => defined('PEKON_PWD') ? constant('PEKON_PWD') : '',
                'tenant'            => defined('PEKON_TENANT') ? constant('PEKON_TENANT') : '',
                'noOrg'             =>  'Yes',
                'headers'       => [
                    'Content-Type' => 'application/json',
                ],
            ];
        }else{
            $channel_type = 'store';
            $node_id = 'pekon';
            $channel_id =  $this->__channelObj->store['store_id'];

            $token = kernel::single('erpapi_router_request')->set('store',$channel_id)->pekon_token();

            $query_params = [
                'api'           => $method,
                'orgClientCode' => defined('PEKON_ORGCLIENTCODE') ? constant('PEKON_ORGCLIENTCODE') : '',

                'headers'       => [
                    'Content-Type' => 'application/json',
                    'ssoSessionId' => $token, 
                ],
            ];
        }
        

        return $query_params;
    }


}
