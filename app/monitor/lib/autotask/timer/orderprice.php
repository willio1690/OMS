<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Vsersion: 2022/10/18
 * @Describe: 系统预警点单价格报警通知
 */
class monitor_autotask_timer_orderprice
{
    
    public function process($params, &$error_msg = '')
    {
        return true;
        set_time_limit(0);
        ignore_user_abort(1);
        @ini_set('memory_limit', '512M');
    
        // 判断执行时间
        base_kvstore::instance('monitor')->fetch('process_orderprice', $lastExecTime);
    
        // 脚本已经执行过
        if ($lastExecTime && $lastExecTime > (time() - 300)) {
            $error_msg = '5分钟内不能重复执行';
            return false;
        }
        base_kvstore::instance('monitor')->store('process_orderprice', time());
        
        $orderRetrialMdl = app::get('ome')->model('order_retrial');
        
        //第一次查所有的
        $filter = [];
        if ($lastExecTime) {
            $filter['dateline']         = $lastExecTime;
            $filter['_dateline_search'] = 'than';
        }
        $filter['status']       = '0';
        $filter['retrial_type'] = 'audit';
        $orderRetrialList = $orderRetrialMdl->getList('order_id',$filter,0,1);
        if ($orderRetrialList) {
            $offset = 0;
            $limit  = 500;
            do {
                $orderRetrialList = $orderRetrialMdl->getList('order_id',$filter,$offset, $limit);
                if (empty($orderRetrialList)) {
                    break;
                }
                $orderIds = array_column($orderRetrialList,'order_id');
                
                $orderMdl = app::get('ome')->model('orders');
                foreach ($orderMdl->getList('*',['order_id'=>$orderIds]) as $value) {
                    $orders[$value['order_id']] = $value;
                }
                $objectMdl = app::get('ome')->model('order_objects');
                foreach ($objectMdl->getList('*',['order_id'=>$orderIds]) as $value) {
                    $orders[$value['order_id']]['order_objects'][$value['obj_id']] = $value;
                }
                $itemMdl = app::get('ome')->model('order_items');
                $order_items = $itemMdl->getList('*',['order_id'=>$orderIds]);
                foreach ($order_items as &$value) {
                    $value['quantity'] = $value['nums'];
                    $orders[$value['order_id']]['order_objects'][$value['obj_id']]['order_items'][$value['item_id']] = $value;
                }
                foreach ($orders as $val) {
                    list($rs, $msg) = kernel::single('ome_order_retrial')->checkMonitorAbnormal($val);
                    if($rs) {
                        $regex="#物料:(.*)异常#";
                        preg_match_all($regex,$msg,$result);
                        $bn = $result[1][0];
                        $regex="#物料名称：(.*)，实#";
                        preg_match_all($regex,$msg,$result);
                        $name = $result[1][0];
                        $regex="#实付：(.*),成#";
                        preg_match_all($regex,$msg,$result);
                        $price = $result[1][0];
                        $regex="#低价：(.*),倍#";
                        preg_match_all($regex,$msg,$result);
                        $warning_price = $result[1][0];
                        if ($bn) {
                            $params = ['order_bn'  => $val['order_bn'],'product_name'=>$name,'bn'=>$bn,'price'=>$price,'warning_price'=>$warning_price,'org_id'=>$val['org_id']];
                            kernel::single('monitor_event_notify')->addNotify('order_items_price', $params);
                        }
                    }
                }
                $offset += $limit;
            } while (true);
            $error_msg = '执行成功';
        }else{
            $error_msg = '暂无内容';
        }
    }
}
