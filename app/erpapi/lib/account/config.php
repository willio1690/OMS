<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2021/4/6 14:32:52
 * @describe: 类
 * ============================
 */
class erpapi_account_config extends erpapi_config {
    protected $requestType = [
        'token' => 'oauth',
        'userinfo' => 'resource',
        'permission' => 'resource',
        'syncpermission' => 'normal',
    ];

    /**
     * 应用级参数
     *
     * @param String $method 请求方法
     * @param Array $params 业务级请求参数
     * @return void
     * @author
     **/

    public function get_query_params($method, $params) {
        $oidcInfo = app::get('ome')->getConf('pam.passport.oidc.info');
        $query_params = [];
        if($this->requestType[$method] == 'oauth') {
            $query_params['headers'] = [
                'Authorization' => 'Basic '. base64_encode($oidcInfo['client_id'].':'.$oidcInfo['client_secret'])
            ];
            
            // $query_params['method'] = $method;
        } elseif($this->requestType[$method] == 'resource') {
            $query_params['headers'] = [
                'Authorization' => 'Bearer '. $params['access_token']
            ];
            $query_params['access_token'] = null;

            // $query_params['method'] = $method;
        } else {
            $query_params = array(
                'method' => 'MD5',
                'appKey' => $oidcInfo['client_id'],
                'version' => 'v1',
                'timestamp' => time()
            );
        }
        return $query_params;
    }
    /**
     * 获取请求地址
     *
     * @param String $method 请求方法
     * @param Array $params 业务级请求参数
     * @param Boolean $realtime 同步|异步
     * @return void
     * @author
     **/
    public function get_url($method, $params, $realtime) {
        $oidcInfo = app::get('ome')->getConf('pam.passport.oidc.info');
        $url = $oidcInfo[$method];
        if($this->requestType[$method] != 'normal') {
            // unset($params['method']);

            // $url .= '?'.http_build_query($params);
        }
        return $url;
    }
    /**
     * 签名
     *
     * @param Array $params 参数
     * @return void
     * @author
     **/
    public function gen_sign($params) {
        if(!$params['data']) {
            return null;
        }
        $oidcInfo = app::get('ome')->getConf('pam.passport.oidc.info');
        $sign = strtoupper(md5($oidcInfo['client_secret'] . self::assemble($params) . $oidcInfo['client_secret']));
        return $sign;
    }
    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
    public function format($params) {
        
        // if ($this->requestType[$params['method']] != 'normal') {
        //     return [];
        // }

        return $params;
    }
}