<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 基础接口相关
 *
 * @categoryclassName
 * @package
 * @version $Id: Z
 */
class erpapi_shop_matrix_website_d1m_request_base extends erpapi_shop_request_abstract
{
    /**
     * 获取token
     * @param $appid
     * @param $secret
     * @return mixed
     */

    public function get_access_token($refresh = true)
    {
        $tokenKey = "d1m_access_token";
        base_kvstore::instance('d1m/api')->fetch($tokenKey, $token);
        if ($token && !$refresh) {
            return $token;
        }
        
        $rs = $this->__caller->call(D1M_ACCESS_TOKEN_POST, [], array(), 'D1M获取accessToken', 10, 'd1m_token');
    
        if ($rs['rsp'] == 'succ') {
            base_kvstore::instance('d1m/api')->store($tokenKey, $rs['data']['token'], 3000);
            base_kvstore::instance('d1m/api')->fetch($tokenKey, $token);
            return $token;
        } else {
            return false;
        }
    }
}