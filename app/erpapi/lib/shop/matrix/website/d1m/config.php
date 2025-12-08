<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * D1M小程序
 * Class erpapi_shop_matrix_website_d1m_config
 */
class erpapi_shop_matrix_website_d1m_config extends erpapi_shop_matrix_website_config
{
    private $__url_mapping = array(
        D1M_ACCESS_TOKEN_POST,
        D1M_OPEN_DELIVERY_UPDATE_POST,
        D1M_OPEN_UPDATE_STORE_POST,
        D1M_OPEN_REFUND_NOTICE_POST,
    );
    
    private $__api_auth_list = array(
        D1M_OPEN_DELIVERY_UPDATE_POST,
        D1M_OPEN_UPDATE_STORE_POST,
        D1M_OPEN_REFUND_NOTICE_POST,
    );
    
    /**
     * 初始化
     * @param erpapi_channel_abstract $channel channel
     * @return mixed 返回值
     */

    public function init(erpapi_channel_abstract $channel)
    {
        return parent::init($channel);
    }
    
    /**
     * 应用级参数
     *
     * @param String $method 请求方法
     * @param Array $params 业务级请求参数
     * @return Array
     * @author
     **/
    public function get_query_params($method, $params)
    {
        $query_params['appKey'] = $this->__channelObj->channel['config']['website_d1m_request_appkey'];
        // 认证token
        if (in_array($method, $this->__api_auth_list)) {
            $token                                    = $this->__get_token();
            $query_params['headers']['Authorization'] = 'Bearer ' . $token;
            $query_params['headers']['Content-Type']  = 'application/json';
        }else{
            $query_params['secret'] = $this->__channelObj->channel['config']['website_d1m_request_secret'];
        }
        
        return $query_params;
    }
    
    /**
     * 获取请求地址
     *
     * @param String $method 请求方法
     * @param Array $params 业务级请求参数
     * @param Boolean $realtime 同步|异步
     * @return String
     * @author
     **/
    public function get_url($method, $params, $realtime)
    {
        $url = $this->__channelObj->channel['config']['website_d1m_url'];
        #匹配
        $url .= $method;
        return $url;
    }
    
    public function format($query_params)
    {
        if($query_params['json_data']) {
            return $query_params['json_data'];
        }
        unset($query_params['sign']);
        return $query_params;
    }
    
    /**
     * 签名
     *
     * @param Array $params 参数
     *
     * @return void
     * @author
     **/
    public function gen_sign($params)
    {
        $appKey            = $this->__channelObj->channel['config']['website_d1m_request_appkey'];
        $responseAppSecret = $this->__channelObj->channel['config']['d1m_response_secret'];
    
        $sign = '';
        if (isset($params['appKey']) && $params['appKey'] == $appKey) {
            return $sign;
        }
    
        return parent::response_sign($params, $responseAppSecret);
    }
    private function __get_token()
    {
        return kernel::single('erpapi_router_request')->set('shop', $this->__channelObj->channel['shop_id'])->base_get_access_token(false);
    }
    
    public function whitelist($apiname)
    {
        if(in_array($apiname,$this->__url_mapping)){
            return true;
        }else{
            return false;
        }
    }
}