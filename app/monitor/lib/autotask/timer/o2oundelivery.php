<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * POS未发货报警
 */
class monitor_autotask_timer_o2oundelivery
{
    
    public function process($params, &$error_msg = '')
    {
         
        set_time_limit(0);
        ignore_user_abort(1);
        @ini_set('memory_limit', '512M');
    
        // 判断执行时间
        base_kvstore::instance('monitor')->fetch('pos_o2oundelivery', $lastExecTime);
    
        // 脚本已经执行过
        if ($lastExecTime && $lastExecTime > (time() - 300)) {
            $error_msg = '5分钟内不能重复执行';
            return true;
        }
        base_kvstore::instance('monitor')->store('pos_o2oundelivery', time());

        // 判断是否配置了模板
        $eventTemplateMdl = app::get('monitor')->model('event_template');

        $templateCount    = $eventTemplateMdl->count([
            'event_type' => 'pos_o2oundelivery',
            'status'     => '1',
            'disabled'   => 'false',
        ]);
        if (!$templateCount) {
            $error_msg = '未配置模板';
            return true;
        }

        
        $orderMdl = app::get('ome')->model('orders');
        
        $downtime = time()-300;
        $filter = [

            'shop_type' =>  'pekon',
            'order_bool_type'=>ome_order_bool_type::__O2OPICK_CODE,
            'process_status'    =>array('unconfirmed','confirmed'),

           'download_time|lthan'=>$downtime,
        ];
       
       
        $orderList = $orderMdl->getList('order_id,order_bn,order_bool_type',$filter);

        $order_bools = array_column($orderList,null,'order_bn');

        $booltypeLib = kernel::single('ome_order_bool_type');
        if ($orderList) {
            
            $order_bns = array_column($orderList,'order_bn');

            foreach($order_bns as &$v){

                if($booltypeLib->isMaintain($order_bools[$v]['order_bool_type'])){
                    $v.="【维修】";
                }
               
            }
            $params = [
                    'order_bns'  => implode(',',$order_bns),
                    
            ];

            kernel::single('monitor_event_notify')->addNotify('pos_o2oundelivery', $params);
            $error_msg = '执行成功';
        }else{
            $error_msg = '暂无内容';
        }

        return true;
    }
}
