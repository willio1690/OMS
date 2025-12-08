<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class monitor_ctl_admin_alarm_queue extends desktop_controller 
{
    public function index()
    {
        $queues = kernel::single('monitor_queue')->getQueues();

        $this->pagedata['queues'] = $queues;

        $this->page('admin/alarm/queue.html');
    }
    public function ajaxGetQueues()
    {
        $queues = kernel::single('monitor_queue')->getQueues();

        $this->splash('success', null, null, 'redirect', ['queues' => $queues]);
    }
}
