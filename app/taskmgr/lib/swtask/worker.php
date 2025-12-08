<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 消费器处理
 */
class taskmgr_swtask_worker
{
    private static $timeout = 10;

    public $_taskConf = [];

    /**
     * 处理
     *
     * @return array
     * @author
     **/
    public function run($taskName, $taskConf)
    {
        // $connecterClass = sprintf('taskmgr_connecter_%s', __CONNECTER_MODE);

        // $connecter = new $connecterClass();

        // $config = strtoupper(sprintf('__%s_CONFIG', __CONNECTER_MODE));

        // $isConnect = $connecter->load($taskName, $GLOBALS[$config]);

        $this->_taskConf = $taskConf;

        if (isset($this->_taskConf['timeout']) && $this->_taskConf['timeout'] && is_numeric($this->_taskConf['timeout'])) {
            self::$timeout = $this->_taskConf['timeout'];
        }

        $connecter = taskmgr_swprocess_queue::getDriver($taskName);

        if (!$connecter) {
            return [false, sprintf('%s-%s服务未启用', $taskName, __CONNECTER_MODE)];
        }

        try {
            $connecter->consume(array($this, 'doTask'));
        }catch (\Exception $e){
            return [false, sprintf('%s-%s服务中断: %s', $taskName, __CONNECTER_MODE, $e->getMessage())];
        }

        return [true, '处理完成'];
    }

    /**
     * 任务处理
     *
     * @return void
     * @author
     **/
    public function doTask($message, $queue = null)
    {
        if (empty($queue)) {
            $queue = $message;
        }

        $body = $message->getBody();

        if ($body) {
            $content = json_decode($body, true);

            $s = microtime(true);

            $response = $this->curl($content);

            $e = microtime(true);

            $logInfo = sprintf('task:%s，url:%s，spend:%s，code:%s，request:%s，response:%s(pid:%s,wid:%s)', $content['data']['task_type'], $response['url'], ($e - $s), $response['code'], json_encode($content['data']), $response['body'], getmypid(), 0);
            taskmgr_log::info($logInfo, [], $content['data']['task_type']);

            $this->requeue($content, $response);

            //nack不起作用,信息请求处理完后判断结果以后，再判断是否要重新进队列
            $queue->ack($message->getDeliveryTag());
        }
    }

    private function curl($data)
    {

        $ch  = curl_init();
        $url = $data['url'];
        
        // 判断是否环境变量配置LAN_PROXY_IP并且是IP规则，则走代理。
        // 走内网需要在config.php配置文件中定义BASE_URL
        $lan_proxy_ip = getenv('LAN_PROXY_IP');
        if ($lan_proxy_ip && preg_match('/^(\d{1,3}\.){3}\d{1,3}:80$/', $lan_proxy_ip)) {
            curl_setopt($ch, CURLOPT_PROXY, $lan_proxy_ip);
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); // 使用HTTP代理
            
            // url如果上https改成http
            $url = str_replace('https://', 'http://', $url);
        } else if ($lan_proxy_host = getenv('LAN_PROXY_HOST')) {
            $parsedUrl = parse_url($url);

            list($host, $port) = explode(':', $lan_proxy_host);

            $parsedUrl['host'] = $host;
            $parsedUrl['scheme'] = 'http';

            if ($port && !in_array($port, ['80', '443'])) {
                $parsedUrl['port'] = $port;
            }

            if ($port == '443') {
                $parsedUrl['scheme'] = 'https';
            }


            $rebuildUrl = function() use ($parsedUrl): string {
                // 如果 http_build_url 不可用，则手动构建 URL
                $scheme   = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
                $user     = isset($parsedUrl['user']) ? $parsedUrl['user'] : '';
                $pass     = isset($parsedUrl['pass']) ? ':' . $parsedUrl['pass'] : '';
                $pass     = ($user || $pass) ? "$pass@" : '';
                $host     = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
                $port     = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
                $path     = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
                $query    = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
                $fragment = isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '';
            
                return "$scheme$user$pass$host$port$path$query$fragment";
            };

            $url = $rebuildUrl();
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        //curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::$timeout);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data ? $data['data'] : []);
        $result = curl_exec($ch);
        $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //$result = explode(',', $curl_result);
        curl_close($ch);
        return array('code' => $code, 'body' => $result, 'url' => $url);
    }

    protected function requeue($content, $response)
    {
        if (isset($this->_taskConf['retry']) && $this->_taskConf['retry'] !== true) {
            return;
        }

        if ($response['code'] == 200 && !empty($response['body'])) {
            $result = json_decode($response['body'], true);
            if (is_array($result) && $result['rsp'] == 'succ') {
                return;
            }
        }

        //验签生成，数据压缩
        unset($content['data']['taskmgr_sign']);

        if (!isset($content['data']['fails'])){
            $content['data']['fails'] = 0;
        }

        $content['data']['fails'] += 1;

        //超过3次直接记日志丢掉
        if ($content['data']['fails'] > 3) {
            return true;
        }

        $content['data']['taskmgr_sign'] = taskmgr_rpc_sign::gen_sign($content['data']);

        $message = json_encode($content);

        $routerKey = sprintf('erp.task.%s.*', $content['data']['task_type']);

        $connecter = taskmgr_swprocess_queue::getDriver($content['data']['task_type']);

        $connecter->publish($message, $routerKey);
    }
}
