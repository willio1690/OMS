<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 分派流水单核销检查
 *
 * @author 334395174@qq.com
 * @version 0.1
 */
class financebase_autotask_task_type_verificationAssign extends financebase_autotask_task_init
{

    /**
     * 处理
     * @param mixed $task_info task_info
     * @param mixed $error_msg error_msg
     * @return mixed 返回值
     */

    public function process($task_info,&$error_msg)
    {
        //修改后不需要单独核销
        return true;
        $this->_check_bill();

        $this->_check_ar(); #应收应退核销

        return true;
    }

    /**
     * 实收核销
     *
     * @return void
     * @author 
     **/
    private function _check_bill()
    {
        $mdlBill = app::get('finance')->model('bill');
        $mdlAr   = app::get('finance')->model('ar');

        $current_time = time();
        $queue_mode = 'verificationProcess';
        $page_size = $this->oFunc->getConfig('page_size');
        $order_bn = '';
        $i = 1;

        $file_prefix = md5(KV_PREFIX.$queue_mode.$current_time);
        
        $task_name = "通过流水单核销任务 （ ".date('Y-m-d H:i')." ）" ;

        while (true) {

            $list = $mdlBill->db->select("select order_bn,channel_id as shop_id from `sdb_finance_bill` where `is_check` = 0 and charge_status = 1 and status = 0 and order_bn > '$order_bn' group by order_bn limit $page_size ");
            if(!$list) break;

            $last_index = count($list) - 1; 

            $order_bn = $list[$last_index]['order_bn'];

            $file_name = sprintf("%s_%d.json",$file_prefix,$i);
            $remote_url = financebase_func::storeStorageData($file_name,$list);
            if(!$remote_url) return false;

            $order_bn_ids = array_column($list,'order_bn');

            // 状态改成检查中
            $mdlBill->update(array('is_check'=>1),array('order_bn|in'=>$order_bn_ids,'is_check'=>0));
            $mdlAr->update(array('is_check'=>1),array('order_bn|in'=>$order_bn_ids,'is_check'=>0));

            $queueData = array();
            $queueData['queue_mode'] = $queue_mode;
            $queueData['create_time'] = time();
            $queueData['queue_name'] = sprintf("【 %s 】- 任务%d",$task_name,$i);
            $queueData['queue_data']['remote_url']   = $remote_url;

            $queue_id = $this->oQueue->insert($queueData);
            $queue_id and financebase_func::addTaskQueue(array('queue_id'=>$queue_id),'verificationprocess');
            
            $i++;

        }

        return true;
    }

    /**
     * 仅退款核销
     *
     * @return void
     * @author 
     **/
    private function _check_ar()
    {
        $mdlAr   = app::get('finance')->model('ar');
        $mdlBill = app::get('finance')->model('bill');

        $current_time = time();
        $queue_mode   = 'verificationProcess';
        $page_size    = $this->oFunc->getConfig('page_size');

        $file_prefix = md5(KV_PREFIX.$queue_mode.$current_time.'Ar');
        
        $task_name = "通过销退单核销任务 （ ".date('Y-m-d H:i')." ）" ;

        $i = 1;
        while (true) {
            $list = $mdlAr->db->select("select order_bn,channel_id as shop_id from `sdb_finance_ar` where `is_check` = 0 and charge_status = 1 and status = 0 group by order_bn limit $page_size ");
            if(!$list) break;

            $file_name = sprintf("%s_%d.json",$file_prefix,$i);
            $remote_url = financebase_func::storeStorageData($file_name,$list);
            if(!$remote_url) return false;

            $order_bn_ids = array_column($list,'order_bn');

            // 状态改成检查中
            $mdlAr->update(array('is_check'=>1),array('order_bn|in'=>$order_bn_ids,'is_check'=>0));
            $mdlBill->update(array('is_check'=>1),array('order_bn|in'=>$order_bn_ids,'is_check'=>0));

            $queueData = array();
            $queueData['queue_mode']               = $queue_mode;
            $queueData['create_time']              = time();
            $queueData['queue_name']               = sprintf("【 %s 】- 任务%d",$task_name,$i);
            $queueData['queue_data']['remote_url'] = $remote_url;

            $queue_id = $this->oQueue->insert($queueData);
            $queue_id and financebase_func::addTaskQueue(array('queue_id'=>$queue_id),'verificationprocess');

            $i++;
        }

        return true;
    }

}