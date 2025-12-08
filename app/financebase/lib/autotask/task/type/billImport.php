<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 账单导入处理任务
 *
 * @author 334395174@qq.com
 * @version 0.1
 */
class financebase_autotask_task_type_billImport extends financebase_autotask_task_init
{

    /**
     * 处理
     * @param mixed $task_info task_info
     * @param mixed $error_msg error_msg
     * @return mixed 返回值
     */

    public function process($task_info,&$error_msg)
    {
        $this->oFunc->writelog('对账单导入任务-开始','settlement','任务ID:'.$task_info['queue_id']);

        $storageLib = kernel::single('taskmgr_interface_storage');
        $remote_url = $task_info['queue_data']['remote_url'];
        $local_file = DATA_DIR.'/financebase/tmp_local/'.basename($remote_url);
        $getfile_res = $storageLib->get($remote_url,$local_file);
        $task_info['queue_data']['data'] = array();
        $local_file = is_bool($getfile_res) ? $local_file : $getfile_res;
        if($getfile_res){
            $task_info['queue_data']['data'] = json_decode(file_get_contents($local_file),1);
        }

        $shop_type = $task_info['queue_data']['shop_type'] ? $task_info['queue_data']['shop_type'] : 'alipay';

        $o = kernel::single('financebase_data_bill_'.$shop_type);
        
        $errmsg = array();
        $o->process($task_info['queue_id'],$task_info['queue_data'],$errmsg);

        unlink($local_file);
        $storageLib->delete($remote_url);

        if ($task_info['queue_data']['is_last'] && $begin_time = strtotime($task_info['queue_data']['bill_date'])) {
            // 打到对应的账期
            finance_monthly_report::updateMonthlyAmount(array ('begin_time' => $begin_time));
        }

        if($errmsg){
            $error_msg = $errmsg;
            $this->oFunc->writelog('对账单导入任务-部分成功','settlement','任务ID:'.$task_info['queue_id']);
            return false;
        }else{
            $this->oFunc->writelog('对账单导入任务-完成','settlement','任务ID:'.$task_info['queue_id']);
        }

        return true;
    }
}