<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class monitor_send_notify
{
    
    function run(&$cursor_id, $params, &$errmsg)
    {
        define('FRST_OPER_NAME', 'system');
        define('FRST_TRIGGER_OBJECT_TYPE', '订单导入冻结库存');
        define('FRST_TRIGGER_ACTION_TYPE', 'monitor_send_notify：run');
        $data = explode('::',$params['method']);
        $class = $data[0];
        $method = $data[1];
        kernel::single($class)->$method($params['notify_id']);
        
        return false;
    }
}