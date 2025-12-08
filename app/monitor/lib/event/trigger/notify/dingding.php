<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Vsersion: 2022/10/18
 * @Describe: 预警通知邮件发送
 */
class monitor_event_trigger_notify_dingding extends monitor_event_trigger_notify_common
{
    public function send($notifyInfo)
    {
        if (!$notifyInfo['send_content']) {
            return ['rsp' => 'fail', 'msg' => '发送失败，发送内容为空'];
        }
        if ($notifyInfo['status'] == '1') {
            return ['rsp' => 'fail', 'msg' => '已发送不能重复发送'];
        }

        $headers = [
            'Content-Type' => 'application/json',
        ];
        $data = [
            'msgtype'  => 'markdown',
            'markdown' => [
                'title' => '监控报警',
                'text' => $notifyInfo['send_content'],
            ],
        ];

        $dingding = app::get('monitor')->getConf('dingding.config');
        if (!$dingding['webhook']) {
            return ['rsp' => 'fail', 'msg' => '缺少webhook地址'];
        }

        try {
            $response = $this->curl($dingding['webhook'], json_encode($data, JSON_UNESCAPED_UNICODE), $headers);
            $response = json_decode($response, true);

            return ['rsp' => $response['errcode'] == '0' ? 'succ' : 'fail', 'msg' => $response['errmsg']];
        } catch (Exception $e) {
            return ['rsp' => 'fail', 'msg' => $e->getMessage()];
        }
    }
}
