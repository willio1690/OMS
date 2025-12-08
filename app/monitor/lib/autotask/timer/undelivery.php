<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Vsersion: 2022/10/18
 * @Describe: 系统预警已处理未发货订单列表
 */
class monitor_autotask_timer_undelivery
{
    
    public function process($params, &$error_msg = '')
    {
        set_time_limit(0);
        ignore_user_abort(1);
        @ini_set('memory_limit', '512M');
    
        // 判断执行时间
        base_kvstore::instance('monitor')->fetch('process_undelivery', $lastExecTime);
    
        // 脚本已经执行过
        if ($lastExecTime && date('Y-m-d') == date('Y-m-d', $lastExecTime)) {
            $error_msg = '当天已执行';
            return false;
        }
        
        base_kvstore::instance('monitor')->store('process_undelivery', time());
        
        $orderMdl = app::get('ome')->model('orders');
        $opMdl = app::get('ome')->model('operation_organization');
        
        //针对前一天OMS 4点前已处理未发货的订单
        $startTime = strtotime(date('Y-m-d')) - 86400;
        $endTime = strtotime(date('Y-m-d') .'16:00:00') - 86400;
        
        //没有异常状态、已分派、状态活动、发货状态是未发货或者部分发货，付款时间是当天
        $offset = 0;
        $limit  = 3000;
        $insertData = [];
        do {
            $orderList = $orderMdl->getList('order_bn,dispatch_time,order_source,paytime,org_id,order_type,shop_id',
                [
                    'abnormal'         => 'false',
                    'status'           => 'active',
                    'ship_status'      => ['0', '2'],
                    'paytime|lthan' => $endTime
                ],$offset, $limit);

            if (empty($orderList)) {
                break;
            }

            $orderList = ome_func::filter_by_value($orderList,'org_id');
            $opList = $opMdl->getList('name,org_id',['org_id'=>array_keys($orderList)]);
            $opList = array_column($opList,'name','org_id');
            foreach ($orderList as $orgKey => $orgVal) {
                $title   = 'MP_order_not_shippend_' .$orgKey. date('YmdHis', time());
                $file_path = DATA_DIR . '/export/tmp_local/'. $title . $orgKey . '.csv';
                if (!$insertData[$orgKey]) {
                    $insertData[$orgKey] = ['title' => $title, 'path' => $file_path,'org_id'=>$orgKey,'org_name'=>$opList[$orgKey]];
                }else{
                    $file_path = $insertData[$orgKey]['path'];
                }
                
                if ($offset == 0) {
                    $file_title = implode(',', ['订单号','分派时间','渠道','付款时间']) . "\n";
                    file_put_contents($file_path, chr(0xEF).chr(0xBB).chr(0xBF), FILE_APPEND | LOCK_EX);
                    file_put_contents($file_path, $file_title, FILE_APPEND | LOCK_EX);
                }
    
                foreach ($orgVal as $val) {
                    $val['order_bn']      = $val['order_bn'] . "\t";
                    $val['dispatch_time'] = !empty($val['dispatch_time']) ? date('Y-m-d H:i:s', $val['dispatch_time']) : '';
                    $val['paytime']       = !empty($val['paytime']) ? date('Y-m-d H:i:s', $val['paytime']) : '';
                    $val['order_source']  = $this->getOrderSource($val['order_source'],$val['order_type'],$val['shop_id']) ?? $val['order_source'];
                    unset($val['org_id'],$val['order_type'],$val['shop_id']);
                    $content              = implode(',', $val) . "\n";
                    file_put_contents($file_path, $content, FILE_APPEND | LOCK_EX);
                }
            }
            $offset += $limit;
        } while (true);
        
        if ($insertData) {
            foreach ($insertData as $key => $val) {
                $params = [
                    'content'  => '【'.$val['org_name'].'】已处理未发货的订单信息：'.$val['title'],
                    'file_path'=>[$val['path']],
                    'org_id'=>$val['org_id']
                ];
                kernel::single('monitor_event_notify')->addNotify('process_undelivery', $params, true);
            }
        }else{
            $params = [
                'content'  => '暂无已处理未发货的订单信息',
            ];
            kernel::single('monitor_event_notify')->addNotify('process_undelivery', $params);
        }

        return true;
    }
    
    public function getOrderSource($type,$order_type,$shop_id)
    {
        static $shops;
        $source = ome_mdl_orders::$order_source;
        if ($type == 'direct') {
            if ($order_type != 'offline') {
                //线上订单
                $shopMdl = app::get('ome')->model('shop');
            }else{
                //线下订单
                $shopMdl = app::get('o2o')->model('store');
            }
            if ($shops[$shop_id]) {
                if (!empty($shops[$shop_id]['order_source'])) {
                    return $shops[$shop_id]['order_source'];
                }
            }else{
                $shops[$shop_id] = $shopMdl->db_dump(['shop_id'=>$shop_id],'order_source');
                if (!empty($shops[$shop_id]['order_source'])) {
                    return $shops[$shop_id]['order_source'];
                }
            }
        }
        return isset($source[$type]) ? $source[$type] : $type;
    }
}
