<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 账单处理任务
 *
 * @author 334395174@qq.com
 * @version 0.1
 */
class financebase_autotask_task_process extends financebase_autotask_task_init
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
        @ini_set('memory_limit','512M');

        $filter = array();
        $params['queue_id'] and $filter['queue_id'] = $params['queue_id'];
        $filter['status'] = 'ready';

        // 获取检测任务
        $task_info = $this->oQueue->getList('queue_id,queue_name,queue_mode,queue_data,queue_no,download_id,retry_count',$filter,0,1);
        if($task_info){
            $task_info = $task_info[0];
            $task_info['queue_data'] = unserialize($task_info['queue_data']);

            $class_name = sprintf("financebase_autotask_task_type_".$task_info['queue_mode']);

            if (ome_func::class_exists($class_name) && $instance = kernel::single($class_name)){
                if (method_exists($instance,'process')){
                    $this->oQueue->update(array('status'=>'process','modify_time'=>time()),array('queue_id'=>$task_info['queue_id']));

                    // $msg = '';

                    $rs = $instance->process($task_info,$msg);
                    $retry_count = (int)$task_info['retry_count'] + 1;//重试次数
                    if($rs){
                        $this->oQueue->update(array('status'=>'succ','modify_time'=>time(), 'retry_count' => $retry_count),array('queue_id'=>$task_info['queue_id']));
                    }else{
                        $this->oQueue->update(array('status'=>'error','modify_time'=>time(),'error_msg'=>$msg, 'retry_count' => $retry_count),array('queue_id'=>$task_info['queue_id']));
                    }
                }else{
                    $this->oFunc->writelog('对账单导入任务-处理方法不存在','settlement','任务ID:'.$task_info['queue_id']);
                    $this->oQueue->update(array('status'=>'error','modify_time'=>time(),'error_msg'=>array('处理方法不存在')),array('queue_id'=>$task_info['queue_id']));
                }
            }else{
                $this->oFunc->writelog('对账单导入任务-处理类不存在','settlement','任务ID:'.$task_info['queue_id']);
                $this->oQueue->delete(array('status'=>'error','modify_time'=>time(),'error_msg'=>array('处理方法不存在')),array('queue_id'=>$task_info['queue_id']));
            }
        }
        
        return true;

    }


    
}