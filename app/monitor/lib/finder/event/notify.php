<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Date: 2023/2/16
 * @Describe: 预警消息finder类
 */
class monitor_finder_event_notify
{

    var $detail_basic = '详情';
    function detail_basic($notify_id)
    {
        $render = app::get('ome')->render();
        $eventNotifyMdl = app::get('monitor')->model('event_notify');
        $notifyInfo = $eventNotifyMdl->db_dump($notify_id);
        $render->pagedata['detail'] = $notifyInfo;
    
        return $render->fetch('admin/alarm/event/detail_basic.html','monitor');
    }
}