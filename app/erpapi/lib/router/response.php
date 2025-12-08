<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * RESPONSE 路由
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
@include_once(dirname(__FILE__).'/../apiname.php');
class erpapi_router_response
{
    /**
     * 渠道节点
     * 
     * @var string
     * */
    private $__node_id;

    /**
     * 接口名，如:wms.delivery.status_update
     * 
     * @var string
     * */
    private $__api_name;

    /**
     * 渠道ID
     * 
     * @var string
     * */
    private $__channel_id;

    /**
     * 并发KEY
     * 
     * @var string
     * */
    private $__concurrent_key = '';

        /**
     * 设置_node_id
     * @param mixed $node_id ID
     * @return mixed 返回操作结果
     */

    public function set_node_id($node_id)
    {
        $this->__node_id = $node_id;
        return $this;
    }

    /**
     * 设置_channel_id
     * @param mixed $channel_id ID
     * @return mixed 返回操作结果
     */
    public function set_channel_id($channel_id)
    {
        $this->__channel_id = $channel_id;
        return $this;
    }

    /**
     * 设置_api_name
     * @param mixed $api_name api_name
     * @return mixed 返回操作结果
     */
    public function set_api_name($api_name)
    {
        $this->__api_name = $api_name;
        return $this;
    }

    private function _parse_api_name()
    {
        $pieces       = explode('.', $this->__api_name);
        $channel_type = array_shift($pieces);
        $method       = array_pop($pieces);
        $business     = implode('_', $pieces);

        // list($channel_type, $business, $method) = ;

        if ($channel_type == 'ome') {
            $channel_type = 'shop';
        }

        return array($channel_type, $business, $method);
    }

    /**
     * dispatch
     * @param mixed $params 参数
     * @param mixed $sign_check sign_check
     * @return mixed 返回值
     */
    public function dispatch($params, $sign_check = false)
    {
        $this->__start_time = microtime(true);

        try {
            // 节点和ID都不存在，抛出异常
            if (!$this->__node_id && !$this->__channel_id) {
                throw new erpapi_exception("节点参数必填");
            }

            // 接口名不存在抛出异常
            if (!$this->__api_name) {
                throw new erpapi_exception("接口名称必填");
            }

            list($channel_type, $business, $method) = $this->_parse_api_name();

            // 实例化渠道类
            $channel_name  = 'erpapi_channel_' . $channel_type;
            $channel_class = kernel::single($channel_name, array($this->__node_id, $this->__channel_id));

            if (!$channel_class instanceof erpapi_channel_abstract) {
                throw new erpapi_exception("{$channel_name} not instanceof erpapi_channel_abstract");
            }

            $channelRs = $channel_class->init($this->__node_id, $this->__channel_id);
            if (!$channelRs) {
                throw new erpapi_exception("节点不存在");
            }

            $adapter  = $channel_class->get_adapter();
            $platform = $channel_class->get_platform();
            $ver      = $channel_class->get_ver();

            // 签名验证
            if (in_array($adapter, array('matrix', 'openapi', 'prism')) && $sign_check) {
                $signRs = $this->_check_sign($channel_class, $params);

                if ($signRs == false) {
                    throw new erpapi_exception("签名错误");
                }

                return true;
            }
            if ($channel_type == 'front' && $sign_check) {
                return true;
            }

            // 默认数据转换类
            $object_class = $this->_get_object_class($channel_class, $params);

            // 防并发
            $this->__concurrent_key = '';
            if (method_exists($object_class, 'concurrentKey')) {
                $this->__concurrent_key = $object_class->concurrentKey($params);
                if ($this->__concurrent_key) {
                    // 判断是否在任务执行中
                    $original_bn = $object_class->__apilog['original_bn'];
                    $lastmodify  = $object_class->__lastmodify;
                    $cacheData   = cachecore::fetch($this->__concurrent_key);
                    if (is_array($cacheData) && 'running' == $cacheData['status']) {
                        $mqmsg = '';
                        if ($this->__api_name == 'shop.order.add' && $lastmodify > $cacheData['lastmodify']) {
                            $mqmsg = $this->reWriteToMQ($params, $params['order_bn']);
                        }
                        // 制空，不删除
                        $this->__concurrent_key = '';
                        throw new erpapi_exception("订单正在处理，请稍后请求!" . $mqmsg);
                    }
                    $cacheData = array(
                        'status'     => 'running',
                        'lastmodify' => $lastmodify,
                    );

                    cachecore::store($this->__concurrent_key, $cacheData, 60);
                }
            }

            $object_class->init($channel_class);
            if (method_exists($object_class, $method)) {
                // 数据转成标准格式
                $convert_params = $object_class->{$method}($params);
            } else {
                throw new erpapi_exception(sprintf('The required method "%s" does not exist for %s', $method, get_class($object_class)));
            }

            $title          = $object_class->__apilog['title'];
            $original_bn    = $object_class->__apilog['original_bn'];
            $convert_result = $object_class->__apilog['result'];
            if (!$convert_params) {
                $msg_code = $convert_result['msg_code'] ? $convert_result['msg_code'] : '';
                $this->_write_log($title, $original_bn, 'fail', $params, $convert_params, $convert_result);
                
                $failResult =  array('rsp' => 'fail', 'msg' => $convert_result['msg'], 'msg_code' => $msg_code, 'data' => $convert_result['data']);
                // custom 兼容部分需要返回succ的场景,判断$convert_result 是否有给出rsp值
                if(isset($convert_result['rsp']) && $convert_result['rsp']){
                    $failResult['rsp'] = $convert_result['rsp'];
                }
             
                return $failResult;
            }

            // 数据验证
            try {
                $params_name = 'erpapi_' . $channel_type . '_response_params_' . $business;
                if (class_exists($params_name)) {
                    $valid = kernel::single($params_name, array($channel_class))->check($convert_params, $method);

                    if ($valid['rsp'] != 'succ') {

                        $this->_write_log($title, $original_bn, 'fail', $params, $convert_params, $valid);

                        return array('rsp' => 'fail', 'msg' => $valid['msg'], 'msg_code' => '', 'data' => $convert_result['data']);
                    }
                }
            } catch (Exception $e) {}

            // 最终的处理
            $result = kernel::single('erpapi_' . $channel_type . '_response_process_' . $business, array($channel_class))->{$method}($convert_params);

            $status = ($result['rsp'] == 'succ' || $result['rsp'] == 'success') ? 'success' : 'fail';
            $this->_write_log($title, $original_bn, $status, $params, $convert_params, $result);

            if ($result['rsp'] != 'succ' && $result['rsp'] != 'success') {
                $apiParms = $result['data'];

                $result['data'] = $convert_result['data'];
                //放入失败队列处理
                $errorCode = kernel::single('erpapi_errcode')->getErrcode($channel_type); //错误码

                if ($errorCode && (in_array($result['res'], array_keys($errorCode)) || in_array($result['msg_code'], array_keys($errorCode)))) {
                    if (!$apiParms['obj_type']) {
                        $apiParms['obj_type'] = $errorCode[$result['msg_code']]['obj_type'];
                    }

                    $failApiModel = app::get('erpapi')->model('api_fail');

                    $failApiModel->publish_api_fail($this->__api_name, $apiParms, $result);
                }
                return $result;
            }
            $result['data'] = $result['data'] ? $result['data'] : $convert_result['data'];
            return $result;
        } catch (erpapi_exception $e) {
            $result['msg'] = $e->getMessage() . '(' . $e->getTraceAsString() . ')';

            $original_bn = $original_bn ? $original_bn : 'logic-exception';
            $this->_write_log('业务异常', $original_bn, 'fail', $params, array(), $result);

            return array('rsp' => 'fail', 'msg' => $e->getMessage(), 'msg_code' => '', 'data' => null);
        } catch (Exception $e) {
            // 异常上报
            \Sentry\captureException($e);
            $result['msg'] = $e->getMessage() . '(' . $e->getTraceAsString() . ')';

            $original_bn = $original_bn ? $original_bn : 'response-exception';
            $this->_write_log('错误异常', $original_bn, 'fail', $params, array(), $result);

            return array('rsp' => 'fail', 'msg' => $e->getMessage(), 'msg_code' => '', 'data' => null);
        }
    }

    /**
     * 获取处理类
     * 
     * @return void
     * @author
     * */
    private function _get_object_class($channel_class, $params)
    {
        list($channel_type, $business, $method) = $this->_parse_api_name();

        $adapter  = $channel_class->get_adapter();
        $platform = $channel_class->get_platform();
        $platform_business = $channel_class->get_platform_business();
        $ver      = $channel_class->get_ver();

        $default_object_name = 'erpapi_' . $channel_type . '_response_' . $business;

        $object_name_arr = array('erpapi', $channel_type, $adapter, $platform, 'response', $business);

        $object_name = implode('_', array_filter($object_name_arr));
        try {
            if (class_exists($object_name)) {
                $object_class = kernel::single($object_name, array($channel_class));

                if (!is_subclass_of($object_class, $default_object_name)) {
                    throw new Exception("{$object_name} is a subclass of {$default_object_name}");
                }

            }
        } catch (Exception $e) {

        }

        if (!is_object($object_class) && false !== strpos($platform, 'shopex_')) {
            $parentPlatForm = 'shopex';

            // 自带处理类
            $object_name_arr = array('erpapi', $channel_type, $adapter, $parentPlatForm, 'response', $business);
            $object_name     = implode('_', array_filter($object_name_arr));
            try {
                if (class_exists($object_name)) {
                    $object_class = kernel::single($object_name, array($channel_class));

                    if (!is_subclass_of($object_class, $default_object_name)) {
                        throw new Exception("{$object_name} is a subclass of {$default_object_name}");
                    }

                }
            } catch (Exception $e) {

            }
        }

        if (!is_object($object_class) && false !== strpos($platform, 'pos_')) {
            $parentPlatForm = 'pos';

            // 自带处理类
            $object_name_arr = array('erpapi', $channel_type, $adapter, $parentPlatForm, 'response', $business);
            $object_name     = implode('_', array_filter($object_name_arr));
            try {
                if (class_exists($object_name)) {
                    $object_class = kernel::single($object_name, array($channel_class));
              
                    if (!is_subclass_of($object_class, $default_object_name)) {
                        throw new Exception("{$object_name} is a subclass of {$default_object_name}");
                    }

                }
            } catch (Exception $e) {

            }
        }
        
        // 对象内业务流转 (不建议使用， 改用platform_business)
        if (is_object($object_class) && method_exists($object_class, 'business_flow')) {
            $business_name = $object_class->business_flow($params);

            try {
                if (class_exists($business_name)) {
                    $object_class = kernel::single($business_name, array($channel_class));

                    if (!is_subclass_of($object_class, $default_object_name)) {
                        throw new Exception("{$object_name} is a subclass of {$default_object_name}");
                    }

                }
            } catch (Exception $e) {}
        }

        if($platform_business) {
            // 将$platform_business插入到数据$object_name_arr的倒数第二位
            array_splice( $object_name_arr, -1, 0, [$platform_business]);

            // $object_name_arr = array('erpapi', $channel_type, $adapter, $platform, 'response', $platform_business, $business);

            $object_name = implode('_',array_filter($object_name_arr));

            try {
                if (class_exists($object_name)) {
                    $pb_object_class = kernel::single($object_name,array($channel_class));

                    if (is_subclass_of($pb_object_class, $default_object_name)) {
                        $object_class = $pb_object_class;
                    }
                }
            } catch (Exception $e) {

            }
        }

        // 取默认
        if (!is_object($object_class)) {
            $object_class = kernel::single($default_object_name, array($channel_class));
        }

        return $object_class;
    }

    /**
     * 验证签名
     * 
     * @return void
     * @author
     * */
    private function _check_sign($channel_class, $params)
    {
        list($channel_type, $business, $method) = $this->_parse_api_name();
        $adapter                                = $channel_class->get_adapter();
        $platform                               = $channel_class->get_platform();
        $ver                                    = $channel_class->get_ver();

        // 默认
        $config_class = kernel::single('erpapi_' . $channel_type . '_config', array($channel_class));

        // 如果有自身配置
        try {
            if (class_exists('erpapi_' . $channel_type . '_' . $adapter . '_' . $platform . '_config')) {
                $config_class = kernel::single('erpapi_' . $channel_type . '_' . $adapter . '_' . $platform . '_config', array($channel_class));
            }
        } catch (Exception $e) {
            try {
                if (class_exists('erpapi_' . $channel_type . '_' . $adapter . '_config')) {
                    $config_class = kernel::single('erpapi_' . $channel_type . '_' . $adapter . '_config', array($channel_class));
                }
            } catch (Exception $e) {

            }
        }

        $config_class->init($channel_class);

        // 签名
        $sign     = $params['sign'];unset($params['sign']);
        $erp_sign = $config_class->gen_sign($params);

        if ($sign != $erp_sign) {
            return false;
        }

        return true;
    }

    /**
     * 日志
     * 
     * @return void
     * @author
     * */
    private function _write_log($title, $original_bn, $status = 'succ', $params = array(), $convert_params = array(), $result = array())
    {
        if ($this->__concurrent_key) {
            cachecore::store($this->__concurrent_key, '', 1);
        }

        // 写日志
        $apilogModel = app::get('ome')->model('api_log');
        $log_id      = $apilogModel->gen_id();

        if ($params['task'] && $result['rsp'] == 'succ') {
            $apilogModel->set_repeat($params['task'], $log_id);
        }
        $logParams = json_encode($params);
        $traParams = json_encode($convert_params);
        // $msg    = '接收参数：' . var_export($params, true) . '<hr/>转换后参数：' . var_export($convert_params, true) . '<hr/>返回结果：' . var_export($result, true);
        
        // url
        $request_uri = base_request::get_request_uri();
        $source_url = $request_uri ? (strpos($request_uri, '?') !== false ? substr($request_uri, 0, strpos($request_uri, '?')) : $request_uri) : '';
        
        // sdf
        $logsdf = array(
            'log_id'        => $log_id,
            'task_name'     => $title,
            'status'        => $status,
            'worker'        => $params['method'],
            'params'        => strlen($logParams) < 256000 ? $logParams : '',
            'transfer'      => strlen($traParams) < 256000 ? $traParams : '',
            'response'      => json_encode($result),
            'msg'           => $result['msg'],
            'log_type'      => '',
            'api_type'      => 'response',
            'memo'          => '',
            'original_bn'   => $original_bn,
            'createtime'    => time(),
            'last_modified' => time(),
            'msg_id'        => (string)$params['msg_id'], //矩阵给的msg_id
            'spendtime'     => microtime(true) - $this->__start_time,
            'url'           => $source_url,
        );
        
        $apilogModel->insert($logsdf);
    }

    private static function gen_sign($params)
    {
        return strtoupper(md5(strtoupper(md5(base_certificate::assemble($params))) . 'S89NCHdjs4xd3kjfhec92P'));
    }

        /**
     * reWriteToMQ
     * @param mixed $params 参数
     * @param mixed $tid ID
     * @return mixed 返回值
     */
    public function reWriteToMQ($params, $tid)
    {
        // 放入队列之前，先去重
        $obj_rpc_poll = app::get('base')->model('rpcpoll');
        $rpc_id       = $params['task'] ? $params['task'] : md5($params['task'] . $params['sign'] . $_SERVER['HTTP_HOST']);
        $obj_rpc_poll->deleteRpc($rpc_id, 'response');
        if (defined('SAAS_API_MQ') && SAAS_API_MQ == 'true') {
            
            //获取http或者https
            $http_prefix = kernel::request()->get_schema();
            $http_prefix = strtolower($http_prefix);
            $http_prefix = ($http_prefix ? $http_prefix : 'http');
            
            //params
            foreach ($params as $key => $val) {
                $postAttr[] = $key . '=' . urlencode($val);
            }

            $content                       = array();
            $content['spider_data']['url'] = sprintf("%s://%s%s", $http_prefix, $_SERVER['SERVER_NAME'], $_SERVER['REQUEST_URI']);

            $content['spider_data']['params']    = empty($postAttr) ? '' : join('&', $postAttr);
            $content['relation']['to_node_id']   = base_shopnode::node_id('ome');
            $content['relation']['from_node_id'] = $params['from_node_id'];
            $content['relation']['tid']          = $tid;
            $content['relation']['to_url']       = $content['spider_data']['url'];
            $content['relation']['time']         = time();

            $routerKey = 'tg.sys.api.' . $params['from_node_id'];

            $message = json_encode($content);
            $mq      = kernel::single('base_queue_mq');
            $mq->connect($GLOBALS['_MQ_API_CONFIG'], 'TG_API_EXCHANGE', 'TG_API_QUEUE');
            $mq->publish($message, $routerKey);
            $mq->disconnect();
            return '重新丢回队列:成功';
        } else {
            $push_data = array(
                'task_id'        => $rpc_id,
                '_FROM_MQ_QUEUE' => 'true',
                'task_type'      => 'orderrpc',
            );
            $push_data = array_merge($push_data, $params);

            //callback请求进队列
            $push_params = array(
                'data' => $push_data,
                'url'  => kernel::openapi_url('openapi.autotask', 'service'),
            );
            kernel::single('taskmgr_interface_connecter')->push($push_params);
            return '重新丢回队列:成功';
        }
        return '';
    }
}
