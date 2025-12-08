<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 导入处理任务
 * Class omecsv_autotask_task_type_import
 */
class omecsv_autotask_task_type_import extends omecsv_autotask_task_init
{
    
    public function process($task_info, &$error_msg)
    {
        $this->oFunc->writelog('导入任务-开始', 'settlement', '任务ID:' . $task_info['queue_no']);
        
        $storageLib                      = kernel::single('taskmgr_interface_storage');
        $remote_url                      = $task_info['remote_url'];
        $local_file                      = DATA_DIR . '/omecsv/tmp_local/' . basename($remote_url);
        $getfile_res                     = $storageLib->get($remote_url, $local_file);
        if (!is_bool($getfile_res)) {
            $local_file = $getfile_res;
        }
        $task_info['queue_data']['data'] = array();
        if ($getfile_res) {
            $task_info['queue_data']['data'] = json_decode(file_get_contents($local_file), 1);
        }
        
        //获取白名单配置类
        $billType = kernel::single('omecsv_split_whitelist')->getBillType($task_info['queue_data']['type']);
        $o        = kernel::single($billType['class']);
        
        $errmsg = array();
        $o->process($task_info['queue_id'], $task_info['queue_data'], $errmsg);
        
        unlink($local_file);
        $storageLib->delete($remote_url);
        
        if ($errmsg) {
            $error_msg = $errmsg;
            $this->oFunc->writelog('导入任务-部分成功', 'settlement', '任务ID:' . $task_info['queue_no']);
            return false;
        } else {
            $this->oFunc->writelog('导入任务-完成', 'settlement', '任务ID:' . $task_info['queue_no']);
        }
        
        return true;
    }
}