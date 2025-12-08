<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_caller{

    /**
     * CONFIG
     * 
     * @var erpapi_config
     * */
    private $__config = null;

    /**
     * RESULT
     * 
     * @var erpapi_result
     * */
    private $__result = null;

    /**
     * channel
     * 
     * @var erpapi_channel_abstract
     * */
    private $__channel = null;

        /**
     * 设置_config
     * @param erpapi_config $config 配置
     * @return mixed 返回操作结果
     */
    public function set_config(erpapi_config $config)
    {
        $this->__config = $config;

        return $this;
    }

    /**
     * 设置_result
     * @param erpapi_result $result result
     * @return mixed 返回操作结果
     */
    public function set_result(erpapi_result $result)
    {
        $this->__result = $result;

        return $this;
    }

    /**
     * 设置_channel
     * @param erpapi_channel_abstract $channel channel
     * @return mixed 返回操作结果
     */
    public function set_channel(erpapi_channel_abstract $channel)
    {
        $this->__channel = $channel;

        return $this;
    }


    /**
     * 异步请求
     * 
     * @return void
     * @author 
     * */
    public function call($method,$params,$callback=array(),$title='',$time_out=10,$primary_bn='',$write_log=true,$gateway='',$logData=[])
    {
        // 白名单
        if (false === $this->__config->whitelist($method)) {
            $response['rsp']       = 'fail';
            $response['err_msg']   = '接口被禁止';
            $response['res_ltype'] = 1;

            return $response;
        }

        // 记日志
        $apilogModel = app::get('ome')->model('api_log');
        $log_id = $apilogModel->gen_id();

        if ($callback && $callback['class'] && $callback['method'] && $write_log) {
            $callback['params']['log_id'] = $log_id;
            $callback['params']['obj_bn'] = $primary_bn;
            $callback['params']['request_title'] = $title;
        }

        // 请求
        $realtime = $callback ? false : true;
        $gzip     = isset($params['gzip']) ? $params['gzip'] : false; unset($params['gzip']);
        $rpc_id   = $params['rpc_id'] ? $params['rpc_id'] : null; unset($params['rpc_id']);


        $call_start_time = microtime(true);
        $callerObj = new erpapi_rpc_caller();
        $result = $callerObj->set_timeout($time_out)
                                           ->set_realtime($realtime)
                                           ->set_config($this->__config)
                                           ->set_result($this->__result)
                                           ->set_gateway($gateway)
                                           ->set_callback($callback['class'], $callback['method'], $callback['params'])
                                           ->call($method, $params,  $rpc_id, $gzip);

        $requestBody = $result['requestBody'];unset($result['requestBody']);

        list($usec, $sec) = explode(" ", microtime());
        $call_end_time = $usec + $sec;

        $result['msg'] = $result['err_msg'];
        $status = 'fail';
        if ($result['rsp'] == 'running') {
            $status = 'running';
        } elseif ($result['rsp'] == 'succ') {
            $status = 'success';
        }
        $msg = var_export($result,true);
        $num = 2048 * 1024 / 4;

        // 如果是异常，超时重新加队列
        if ($realtime === false && $result['rsp'] == 'fail') {
            $failApiModel = app::get('erpapi')->model('api_fail');
            $failApiModel->publish_api_fail($method,$callback['params'],$result);
        }

        if ($write_log) {
            // url
            $source_url = isset($result['url']) ? (strpos($result['url'], '?') !== false ? substr($result['url'], 0, strpos($result['url'], '?')) : $result['url']) : '';
            
            // sdf
            $logsdf = array(
                'log_id'        => $log_id,
                'task_name'     => $title,
                'status'        => $status,
                'worker'        => $method,
                'params'        => json_encode($logData ?: $requestBody),
                'transfer'      => json_encode($callback),
                'response'      => json_encode(isset($msg[$num]) ? ['数据太大，不存储返回结果'] : $result),//当返回结果大于2KB时，日记中不写结果
                'msg'           => $result['msg'],
                'log_type'      => '',
                'api_type'      => 'request',
                'memo'          => '',
                'original_bn'   => $primary_bn,
                'createtime'    => $call_start_time,
                'last_modified' => $call_start_time,
                'msg_id'        => $result['msg_id'],
                'spendtime'     => microtime(true) - $call_start_time,
                'url'           => $source_url,
            );

            $logInsertFunc = function () use (&$logsdf) {
                $apilogModel = app::get('ome')->model('api_log');
                $rs = $apilogModel->insert($logsdf);
                return $rs;
            };


            if(kernel::database()->isInTransaction()) {
                register_shutdown_function($logInsertFunc, $logsdf);
            } else {
                $apilogModel = app::get('ome')->model('api_log');
                $apilogModel->insert($logsdf);
            }
        }
        
        if ($result['rsp'] == 'fail') {
            $result['msg'] = mb_substr($result['msg'],0,100);
            $result['err_msg'] = mb_substr($result['err_msg'],0,100);
            if(is_object($this->__channel)) {

                kernel::single('monitor_event_notify')->addNotify('rpc_warning', [
                    'title'     => $title,
                    'bill_bn'   => $primary_bn,
                    'method'    => $method,
                    'errmsg'    => $result['err_msg'],
                ]);

            }
        }
        return $result;
    }

    /**
     * 异步请求回调
     * 
     * @return void
     * @author 
     * */
    public function callback($result)
    {
        if (!is_object($result)) return true;

        $callback_params = $result->get_callback_params();
        $rsp             = $result->get_status();
        $msg_id          = $result->get_msg_id();
        $msg             = $result->get_result();
        $response        = $result->get_response();

        $status = $rsp == 'succ' ? 'success' : 'fail';
        $log_id = $callback_params['log_id'];
        $msg = var_export($response,true);
        if(isset($msg[2048])) { //当返回结果大于2KB时，日记中不写结果
            $msg = $rsp == 'succ' ? '成功' : '失败';
        }

        $apilogModel = app::get('ome')->model('api_log');
        $apilogModel->update(array('status'=>$status,'msg'=>$msg),array('log_id'=>$log_id));


        return array('rsp'=>$rsp, 'res'=>$msg, 'msg_id'=>$msg_id);
    }

   
    #请求SHOPEX中心
        /**
     * center_call
     * @param mixed $method method
     * @param mixed $params 参数
     * @param mixed $time_out time_out
     * @param mixed $http_method http_method
     * @return mixed 返回值
     */
    public function center_call($method, $params, $time_out = 10, $http_method = 'POST')
    {
        $url = MATRIX_RELATION_URL;
        $configParams = $this->__config->get_query_params($method, $params);
        $sys_params = array(
            'app'          => $method,
            'certi_id'     => $configParams['certi_id'],
            'from_node_id' => $configParams['from_node_id'],
            'from_api_v'   => $configParams['from_api_v'],
            'to_node_id'   => $configParams['to_node_id'],
            'to_api_v'     => $configParams['to_api_v'],
            'v'            => $configParams['from_api_v'],
            'node_type'    => $configParams['node_type'],
            'timestamp'    => date('Y-m-d H:i:s',time()),
            'format'       => 'json',
        );
        $query_params = array_merge($sys_params,$params);
        $query_params['certi_ac'] = self::licence_sign($query_params,'ome');
        if ($http_method == 'POST'){
            $http = kernel::single('base_httpclient');
            $response = $http->set_timeout($time_out)->post($url,$query_params);
            $response = json_decode($response,true);
            $rsp = array('rsp'=>'fail','msg'=>'','data'=>'');
            if (!isset($response['res'])){
                $rsp['msg'] = '请求超时';
            }else{
                $rsp['rsp'] = $response['res'];
                $rsp['msg'] = $response['msg'];
                $rsp['data'] = $response['info'];
            }
            return $rsp;
        }else{
            $query_str = array();
            foreach ($query_params as $key=>$value){
                $query_str[] = $key.'='.$value;
            }
            $query_str = implode('&',$query_str);
            $src = $url.'?'.$query_str;
            header('Location:'.$src);
            exit;
        }
    }
    /**
     * licence生成加密串
     * @access public
     * @param $params
     * @return String
     */

    static public function licence_sign($params){
        $str   = '';
        ksort($params);
        foreach($params as $key => $value){
            $str.=$value;
        }
        $token = base_certificate::token();

        return md5($str.$token);
    }

     /**
     * 放mq后台执行请求
     *
     * @return void
     * @author 
     **/
    public function caller_into_mq($method,$channel_type,$channel_id,$params,$queue=false)
    {
        if (!defined('SAAS_API_MQ') 
            || SAAS_API_MQ != 'true' 
            || $queue != true
            ) {
            return false;
        }

        $data = array();
        $data['spider_data']['url'] = kernel::openapi_url('openapi.autotask','service');

        $push_params = array(
            'method'       => $method,
            'channel_type' => $channel_type,
            'channel_id'   => $channel_id,
            'params'       => json_encode($params),
            'log_id'       => uniqid(),
            'task_type'    => 'autoretryapi',
        );
        $push_params['taskmgr_sign'] = taskmgr_rpc_sign::gen_sign($push_params);

        $postAttr = array();
        foreach ($push_params as $key => $val) {
            $postAttr[] = $key . '=' . urlencode($val);
        }

        $data['spider_data']['params']    = empty($postAttr) ? '' : join('&', $postAttr);
        $data['relation']['to_node_id']   = base_shopnode::node_id('ome');
        $data['relation']['from_node_id'] = '0';
        $data['relation']['tid']          = $push_params['log_id'];
        $data['relation']['to_url']       = $data['spider_data']['url'];
        $data['relation']['time']         = time();

        $routerKey = 'tg.sys.api.'.$data['nodeId'];

        $message = json_encode($data);
        $mq = kernel::single('base_queue_mq');
        $mq->connect($GLOBALS['_MQ_API_CONFIG'], 'TG_API_EXCHANGE', 'TG_API_QUEUE');
        $mq->publish($message, $routerKey);
        $mq->disconnect();

        return true;
    }
}
