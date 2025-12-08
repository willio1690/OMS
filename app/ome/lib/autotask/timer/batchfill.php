<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 批量补单处理类
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

class ome_autotask_timer_batchfill
{
    public function process($params, &$error_msg=''){
        set_time_limit(0);
        ignore_user_abort(1);

        /*检查是否漏单begin*/
        kernel::single('ome_rpc_request_miscorder')->getlist_order();

        $ome_syncorder = kernel::single("ome_syncorder");
        $omequeueModel = kernel::single("ome_syncshoporder");
        $apilog = app::get('ome')->model('api_order_log');

        $orderinfo = $omequeueModel->fetchAll($apilog);

        if(!empty($orderinfo)){
            $i=0;
            while(true){
                if(!$orderinfo[$i]['order_bn']) return false;
                $params['order_bn'] = $orderinfo[$i]['order_bn'];
                $params['shop_id'] = $orderinfo[$i]['shop_id'];
                $params['log_id'] = $orderinfo[$i]['log_id'];
                $res = $ome_syncorder->get_order_list_detial($params);
                $i++;
            }
        }
        /*检查是否漏单end*/

        return true;
    }
}