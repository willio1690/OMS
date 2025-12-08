<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class monitor_autotask_timer_checkorderisdelivery {


    public function process($params, &$error_msg = '')
    {
        set_time_limit(0);
        ignore_user_abort(1);
        @ini_set('memory_limit', '1024M');
        $now = time() - 180;
        $last = strtotime('-1 week');
        $sql = "select order_bn,order_bool_type from sdb_ome_orders where is_delivery='N' and status='active' and createtime<{$now} and createtime>{$last} and process_status in ('unconfirmed','confirmed')";
        $list = kernel::database()->select($sql);
        foreach($list as $k => $order) {
            if($order['order_bool_type'] & ome_order_bool_type::__RISK_CODE) {
                unset($list[$k]);
            }
        }
        if($list) {
            kernel::single('monitor_event_notify')->addNotify('order_360buy_delivery_error', [
                'order_bn' => implode(', ', array_column($list, 'order_bn'))
            ]);
        }
        return true;
    }
}