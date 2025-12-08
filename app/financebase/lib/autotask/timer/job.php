<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 定时任务
 *
 * @author 334395174@qq.com
 * @version 0.1
 */
class financebase_autotask_timer_job
{


    /**
     * 处理
     * @param mixed $params 参数
     * @param mixed $error_msg error_msg
     * @return mixed 返回值
     */

    public function process($params, &$error_msg='')
	{
        set_time_limit(0);
        ignore_user_abort(1);
        @ini_set('memory_limit','128M');

        //判断是否安装不安装直接返回成功
        if(!app::get('finance')->is_installed() || !app::get('financebase')->is_installed() || !app::get('finance')->getConf('finance_setting_init_time')){
            return true;
        }


        $this->now_time = time();
        $crontab_path = 'financebase/lib/crontab';
        $path = ( defined('CUSTOM_CORE_DIR') && is_dir(CUSTOM_CORE_DIR.'/'.$crontab_path)  ) ? CUSTOM_CORE_DIR.'/'.$crontab_path : APP_DIR.'/'.$crontab_path;

        foreach (glob($path.'/*.php') as $file_name) {
            $class_name = basename($file_name,'.php');
            $this->class_list[$class_name] = kernel::single('financebase_crontab_'.$class_name);

            $crontab_run_time = $this->class_list[$class_name]->getTime();

            if( $this->class_list[$class_name]->_is_enable && $this->now_time > $crontab_run_time)
            {
                $this->class_list[$class_name]->setTime();
                $this->class_list[$class_name]->process();
            }

        }

        return true;
        
    }





    /**
     * syncBill
     * @return mixed 返回值
     */
    public function syncBill()
    {

        $next_download_time = strtotime(date("Y-m-d",strtotime("+1 day"))." 03:00:00");
        // app::get('financebase')->setConf('bill.sync_download_time',$next_download_time);
        $this->financeObj->store('sync_download_time',$next_download_time);

        $oFunc = kernel::single('financebase_func');

        $oFunc->writelog('定时任务-同步对账单','settlement','开始');
        
        $class_pool = array();
        $node_type_ref = $oFunc->getConfig('node_type');
        
        $mdlShopExtends = app::get('ome')->model('shop_extends');
        $list = $mdlShopExtends->getList('*',array());

        foreach ($list as $v) {
            if(!isset($node_type_ref[$v['node_type']])){
                $oFunc->writelog('支付单对账单-同步任务','settlement',"不支持类型".$v['node_type']);
                continue;
            }

            $class_name = 'financebase_data_bill_'.$node_type_ref[$v['node_type']];

            if(!isset($class_pool[$class_name])){
                if (ome_func::class_exists($class_name)){
                    $class_pool[$class_name] = kernel::single($class_name);
                }else{
                    $oFunc->writelog('支付单对账单-同步任务','settlement',"没有类".$class_name);
                    continue;
                }
            }

            $v['bill_date'] = date("Y-m-d",strtotime("-1 day"));
            $res = $class_pool[$class_name]->doTask($v);
            $oFunc->writelog('支付单对账单-任务开始','settlement',$v);

        }

        $oFunc->writelog('定时任务-同步对账单','settlement','结束');
    }



    /**
     * 自动分派流水单核销检查
     */
    public function autoVerificationByBill()
    {
        
        /*
        $start_time = isset($params['start_time']) ? strtotime($params['start_time']) : strtotime(date("Y-m-d",strtotime("-1 day")));// 开始时间
        $end_time = isset($params['end_time']) ? strtotime($params['end_time']) : strtotime(date("Y-m-d",strtotime("-1 day"))." 23:59:59");//结束时间
        */

        $current_time = time();
        $queue_mode = 'verificationProcess';

        $mdlBill = app::get('finance')->model('bill');
        $oFunc = kernel::single('financebase_func');
        // $storageLib = kernel::single('taskmgr_interface_storage');
        $mdlQueue = app::get('financebase')->model('queue');

        $page_size = $oFunc->getConfig('page_size');
        $page_size = 200;
        $order_bn = '';
        $i = 1;

        $file_prefix = md5($queue_mode.$current_time);
        
        $task_name = "通过流水单核销任务 （ ".date('Y-m-d H:i')." ）" ;

        while (true) {
            //$filter = array('bill_id|than'=>$id,'trade_time|bthan'=>$start_time,'trade_time|sthan'=>$end_time,'status'=>0);
            //$list = $mdlBill->getList('bill_id,bill_bn,unique_id,order_bn,money,fee_type,bill_type,credential_number,channel_id',$filter,0,$page_size,'bill_id');
            // $list = $mdlBill->db->select("select id,bill_bn,unique_id,order_bn,money,trade_type,count(*) as nums from `sdb_finance_bill` where id > $id and `trade_time` >= $start_time and trade_time <= $end_time and trade_type in ('交易付款') and status = 1 group by shop_id,order_bn,trade_type having nums = 1 order by id limit $page_size");
            
            $list = $mdlBill->db->select("select order_bn,channel_id as shop_id from `sdb_finance_bill` where `is_check` = 0 and order_bn > '$order_bn' group by order_bn order by order_bn limit $page_size ");
            if(!$list) break;

            $last_index = count($list) - 1; 

            $order_bn = $list[$last_index]['order_bn'];

            $file_name = sprintf("%s_%d.json",$file_prefix,$i);
            $remote_url = financebase_func::storeStorageData($file_name,$list);
            if(!$remote_url) continue;

            $order_bn_ids = array_column($list,'order_bn');

            // 状态改成检查中
            $mdlBill->update(array('is_check'=>1),array('order_bn|in'=>$order_bn_ids,'is_check'=>0));

            $queueData = array();
            $queueData['queue_mode'] = $queue_mode;
            $queueData['create_time'] = time();
            $queueData['queue_name'] = sprintf("【 %s 】- 任务%d",$task_name,$i);
            $queueData['queue_data']['remote_url']   = $remote_url;

            
        
            $queue_id = $mdlQueue->insert($queueData);
            financebase_func::addTaskQueue(array('queue_id'=>$queue_id),'verificationprocess');
            
            $i++;

        }

    }

   


}