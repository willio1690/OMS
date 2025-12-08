<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 导入分派任务
 *
 * @author 334395174@qq.com
 * @version 0.1
 */
class omecsv_autotask_task_type_assign extends omecsv_autotask_task_init
{
    
    public function process($task_info,&$error_msg)
    {
        if(!is_dir(DATA_DIR.'/omecsv/tmp_local')){
            utils::mkdir_p(DATA_DIR.'/omecsv/tmp_local');
        }
        
        $oTask = kernel::single('omecsv_data_task');
        $storageLib = kernel::single('taskmgr_interface_storage');
        $this->oFunc->writelog('导入任务拆分-开始','settlement','任务ID:'.$task_info['queue_no']);
        
        $remote_url = $task_info['remote_url'];
        $local_file = DATA_DIR.'/omecsv/tmp_local/'.basename($remote_url);
        
        $file_type = $task_info['queue_data']['file_type'];
        
        $newUrl = $storageLib->get($remote_url,$local_file);
        if (!is_bool($newUrl)) {
            $local_file = $newUrl;
        }
        //获取白名单配置类
        $billType = kernel::single('omecsv_split_whitelist')->getBillType($task_info['queue_data']['type']);
        
        list($status,$errmsg) = $oTask->_spliteData($local_file,$file_type,$billType['class'],$task_info['bill_type'],$task_info);
        
        unlink($local_file); $storageLib->delete($remote_url);
        
        if($status){
            $this->oFunc->writelog('导入任务拆分-完成','settlement','任务ID:'.$task_info['queue_no']);
        }else{
            $error_msg[] = $errmsg;
            $this->oFunc->writelog('导入任务拆分-失败','settlement','任务ID:'.$task_info['queue_no']);
            return false;
        }
        
        return true;
        
    }
}