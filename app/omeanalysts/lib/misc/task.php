<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeanalysts_misc_task{

    function week(){

    }

    function minute(){
//         $time = time();
//         $minute = date('i',$time);

//         base_kvstore::instance('setting_taskmgr')->fetch('crontab_get_shoporderlist',$last_crontab_get_shoporderlist);
//         if($last_crontab_get_shoporderlist){
//             if($time >= ($last_crontab_get_shoporderlist + 300)){
//                 base_kvstore::instance('setting_taskmgr')->store('crontab_get_shoporderlist',$time);
//                 /*检查是否漏单begin*/
//                 kernel::single('ome_rpc_request_miscorder')->getlist_order();

//                 $ome_syncorder = kernel::single("ome_syncorder");
//                 $omequeueModel = kernel::single("ome_syncshoporder");
//                 $apilog = app::get('ome')->model('api_order_log');

//                 $orderinfo = $omequeueModel->fetchAll($apilog);

//                 if(!empty($orderinfo)){
//                     $i=0;
//                     while(true){
//                         if(!$orderinfo[$i]['order_bn']) return false;
//                         $params['order_bn'] = $orderinfo[$i]['order_bn'];
//                         $params['shop_id'] = $orderinfo[$i]['shop_id'];
//                         $params['log_id'] = $orderinfo[$i]['log_id'];
//                         $res = $ome_syncorder->get_order_list_detial($params);
//                         $i++;
//                     }
//                 }
//                 /*检查是否漏单end*/
//             }
//         }else{
//             base_kvstore::instance('setting_taskmgr')->store('crontab_get_shoporderlist',$time);
//         }
    }

    function hour(){

    }

    function day(){
        
    }

    function month(){

    }

}