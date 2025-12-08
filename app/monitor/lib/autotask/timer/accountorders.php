<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class monitor_autotask_timer_accountorders
{
    
    public function process($params, &$error_msg = '')
    {
        set_time_limit(0);
        ignore_user_abort(1);
        @ini_set('memory_limit', '512M');
    
        // 判断执行时间
        base_kvstore::instance('monitor')->fetch('rpc_accountorders', $lastExecTime);
    
        // 脚本已经执行过
        if ($lastExecTime && $lastExecTime > (time() - 3600)) {
            $error_msg = '60分钟内不能重复执行';
            return false;
        }
        base_kvstore::instance('monitor')->store('rpc_accountorders', time());
        
      
        $ordersMdl = app::get('ediws')->model('account_orders');

        $time = time()-3600;
        $orderlist = $ordersMdl->getlist('ord_id,orderNo',array('sync_status'=>array('0'),'refType'=>array('1002'),'create_time|lthan'=>$time),0,10);

        if($orderlist){
            $order_bns = array_column($orderlist,'orderNo');

             $params = [
                    'order_bns'  => implode(',',$order_bns),
                    
            ];

            kernel::single('monitor_event_notify')->addNotify('rpc_accountorders', $params);


        }else{
            base_kvstore::instance('ediws/sxsj')->fetch('lastexectime',$lastExecTime);
            $params = [
                    'order_bns'  =>'无未同步单据,上次执行时间:'.date('Y-m-d H:i:s',$lastExecTime),
                    
            ];

            kernel::single('monitor_event_notify')->addNotify('rpc_accountorders', $params);
        }
       

        return true;
    }
    
    
}
