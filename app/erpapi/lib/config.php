<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
abstract class erpapi_config {
    protected $__channelObj = null;

    protected $__whitelist        = array();
    private   $__global_whitelist = array();

    /**
     * 初始化
     * @param erpapi_channel_abstract $channel channel
     * @return mixed 返回值
     */
    public function init(erpapi_channel_abstract $channel) {
        $this->__channelObj = $channel;

        return $this;
    }

    /**
     * 获取_channel
     * @return mixed 返回结果
     */
    public function get_channel() {
        return $this->__channelObj;
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
//        $row = app::get('base')->model('network')->getlist('node_url,node_api', array('node_id' => 1));
//        if ($row) {
//            if (substr($row[0]['node_url'], -1, 1) != '/') {
//                $row[0]['node_url'] = $row[0]['node_url'] . '/';
//            }
//            if ($row[0]['node_api']{0} == '/') {
//                $row[0]['node_api'] = substr($row[0]['node_api'], 1);
//            }
//            $url = $row[0]['node_url'] . $row[0]['node_api'];
//
//            if ($realtime == true)
//                $url .= 'sync';
//        }
        $url = $this->__channelObj->channel['matrix_url'] ?:  MATRIX_URL;

        if (defined('LOGISTICS_SERVICE_AREAS_ALL_GET') && $method == LOGISTICS_SERVICE_AREAS_ALL_GET) return $url.'service';

        if ($realtime == true)
            $url .= 'sync';

        return $url;
    }

    /**
     * 应用级参数
     *
     * @param String $method 请求方法
     * @param Array $params 业务级请求参数
     * @return void
     * @author
     **/
    public function get_query_params($method, $params) {

    }

    /**
     * 签名
     *
     * @param Array $params 参数
     * @return void
     * @author
     **/
    public function gen_sign($params) {
        if (!base_shopnode::token('ome'))
            $sign = base_certificate::gen_sign($params);
        else
            $sign = base_shopnode::gen_sign($params, 'ome');

        return $sign;
    }

    /**
     * 定义应用参数
     *
     * @return void
     * @author
     **/
    public function define_query_params() { }

    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
    public function format($params) {
        return $params;
    }

    /**
     * 白名单
     *
     * @return void
     * @author
     **/
    public function whitelist($apiname) {
        $whitelist = array_merge($this->__global_whitelist, $this->__whitelist);

        return (!$whitelist || in_array($apiname, $whitelist)) ? true : false;
    }

    static function assemble($params) {
        if (!is_array($params))
            return null;
        ksort($params, SORT_STRING);
        $sign = '';
        foreach ($params AS $key => $val) {
            if (is_null($val))
                continue;
            if (is_bool($val))
                $val = ($val) ? 1 : 0;
            $sign .= $key . (is_array($val) ? self::assemble($val) : $val);
        }

        return $sign;
    }
}