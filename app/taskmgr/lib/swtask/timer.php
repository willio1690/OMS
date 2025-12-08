<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 定时器处理
 */
class taskmgr_swtask_timer
{
    /**
     * 处理
     *
     * @return void
     * @author
     **/
    public function run($taskName, $taskConf)
    {
        $second = taskmgr_swtask_parsecrontab::parse($taskConf['rule']);

        if ($second && in_array(date('s'), $second)) {
            // 连接redis
            $taskType       = str_replace('domainqueue', '', $taskName);
            $routerKey      = sprintf('erp.task.%s.*', $taskType);
            // $connecterClass = sprintf('taskmgr_connecter_%s', __CONNECTER_MODE);
            // $rp_connecter   = new $connecterClass();

            // $config = strtoupper(sprintf('__%s_CONFIG', __CONNECTER_MODE));

            // $isConnect = $rp_connecter->load($taskType, $GLOBALS[$config]);

            $rp_connecter = taskmgr_swprocess_queue::getDriver($taskType);

            if (!$rp_connecter) {
                return [false, sprintf('%s-%s服务未启用', $taskName, __CONNECTER_MODE)];
            }

            $params = array(
                'data' => array(
                    'mdkey'     => md5(DOMAIN),
                    'task_type' => $taskType,
                ),
                'url'  => DOMAIN . '/index.php/openapi/autotask/service',
            );

            $params['data']['taskmgr_sign'] = taskmgr_rpc_sign::gen_sign($params['data']);

            $msg = json_encode($params);

            try {
                $push_result = $rp_connecter->publish($msg, $routerKey);
            }catch (\Exception $e){
                return [false, sprintf('%s-%s服务中断', $taskName, __CONNECTER_MODE)];
            }

            if ($push_result !== false) {
                $push_result = 'succ';
            } else {
                $push_result = 'fail';
            }

            taskmgr_log::info(sprintf('%s(pid:%s,wid:%s)', '订阅消息[' . $taskName . ']-' . $push_result, getmypid(), 0), [], $taskName);
        }

        // 一秒一跑
        // sleep(1);

        return [true, '处理完成'];
    }
}
