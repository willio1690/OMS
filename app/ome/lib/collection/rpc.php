<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_collection_rpc{
    public $api;
    public $node_id;
    public $domain;


    /**
     * __construct
     * @return mixed 返回值
     */
    public function __construct(){
        set_time_limit(0);
        $node_id = base_shopnode::node_id('ome');
        $this->node_id = $node_id;
        $this->domain = 'http://'.kernel::request()->get_host();
    }

    public function http($url = '',$time_out = '1',$query_params = array()){
        $http = kernel::single('base_httpclient');
        $response = $http->set_timeout($time_out )->post($url,$query_params,$headers);
        $data = json_decode($response,true);

        #记录请求日志与返回结果
        $log = date('Y-m-d H:i:s').' '.$url."\n";
        $log .= "Request:".json_encode($query_params)."\n";
        $log .= "Result:".json_encode($data)."\n\n";
        $this->log($log);

        return $data;
    }

    public function sign($api_url='',$params = array(),$api_method='post'){
        // OAuth 认证密钥已清除 - 此功能已不再使用
        // 数据采集服务功能已禁用，相关密钥已删除
        // 返回空字符串，避免误用
        return '';
    }

    /**
     * 获取_timestamp
     * @return mixed 返回结果
     */
    public function get_timestamp(){
        $http = kernel::single('base_httpclient');
        $response = $http->set_timeout(5)->post('http://openapi.ishopex.cn/api/platform/timestamp',$query_params,$headers);
        return $response;
    }

    /**
     * log
     * @param mixed $content content
     * @return mixed 返回值
     */
    public function log($content = ''){
        $date = date('Ymd');
        $logDir = ROOT_DIR.'/script/update/logs';
        error_log($content,3,$logDir.'/collections_'.$date.'.txt');
    }

}