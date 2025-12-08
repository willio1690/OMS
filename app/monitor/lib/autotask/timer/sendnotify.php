<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Vsersion: 2022/10/18
 * @Describe: 系统预警发送通知task任务
 */
class monitor_autotask_timer_sendnotify
{
    
    public function process($params, &$error_msg = '')
    {
        set_time_limit(0);
        ignore_user_abort(1);
        @ini_set('memory_limit', '512M');
        if (!isset($params['is_retry'])) {
            $key   = 'monitor_autotask_timer_sendnotify';
            $isRun = cachecore::fetch($key);
            if ($isRun) {
                $error_msg = 'is running';
                return false;
            }
            cachecore::store($key, 'running', 295);
        }
        $eventNotifyMdl = app::get('monitor')->model('event_notify');
        
        $status = '0';
        //重试的状态是失败2
        if ($params['is_retry']) {
            $status = '2';
        }
        
        $offset = 0;
        $limit  = 300;
        
        $filter = [
            'status'  => $status,
            'is_sync' => 'false'
        ];
        if ($params['notify_id']) {
            $filter['notify_id'] = $params['notify_id'];
        }
        
        $notifyList = $eventNotifyMdl->getList('*', $filter, $offset, $limit);
        if (empty($notifyList)) {
            return true;
        }
        // 更新为处理中
        $eventNotifyMdl->update(['status' => '3'], [
            'notify_id' => array_column($notifyList, 'notify_id')
        ]);
        
        foreach ($notifyList as $val) {
            kernel::single('monitor_event_notify')->sendNotify($val['notify_id'], $val);
            usleep(1000);
        }
        
        return true;
    }
}
