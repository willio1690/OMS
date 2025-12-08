<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 公共方法
 */
class taskmgr_func
{
    /**
     * 分RDS多队列
     *
     * @return void
     * @author
     **/
    public static function multiQueue($queue_config, $queue_exchange, $queue_name, $routerKey, $data, $type = 'task', $uniqid = '0')
    {
        //获取http或者https
        $http_prefix = kernel::request()->get_schema();
        $http_prefix = strtolower($http_prefix);
        $http_prefix = ($http_prefix ? $http_prefix : 'http');
        
        if ($task == 'api') {
            $url = sprintf("%s://%s%s", $http_prefix, $_SERVER['SERVER_NAME'], $_SERVER['REQUEST_URI']);

            // $data['_FROM_MQ_QUEUE'] = true;
        } else {
            $url = kernel::openapi_url('openapi.autotask', 'service');

            $data['taskmgr_sign'] = taskmgr_rpc_sign::gen_sign($data);
        }

        $mq = kernel::single('base_queue_mq');
        $mq->connect($queue_config, $queue_exchange, $queue_name);

        $postAttr = array();
        foreach ($data as $key => $val) {
            $postAttr[] = $key . '=' . urlencode($val);
        }

        $message                       = array();
        $message['spider_data']['url'] = $url;

        $message['spider_data']['params']    = empty($postAttr) ? '' : join('&', $postAttr);
        $message['relation']['to_node_id']   = base_shopnode::node_id('ome');
        $message['relation']['from_node_id'] = '0';
        $message['relation']['tid']          = $uniqid ? $uniqid : $data['uniqid'];
        $message['relation']['to_url']       = $message['spider_data']['url'];
        $message['relation']['time']         = time();

        $mq->publish(json_encode($message), $routerKey);

        $mq->disconnect();
    }

    public static function singleQueue($data)
    {
        $message = array(
            'data' => $data,
            'url'  => kernel::openapi_url('openapi.autotask', 'service'),
        );

        kernel::single('taskmgr_interface_connecter')->push($message);
    }

    /**
     *
     *
     * @return void
     * @author
     **/
    public static function publishMsg($data, $type = 'task', $uniqid = '0', $config_args = [])
    {
        if ($config_args['SAAS_MQ'] == 'true') {
            self::multiQueue($config_args['queue_config'],
                $config_args['queue_exchange'],
                $config_args['queue_name'],
                $config_args['routerKey'],
                $data,
                $type,
                $uniqid);
        } else {
            self::singleQueue($data);
        }
    }
}
