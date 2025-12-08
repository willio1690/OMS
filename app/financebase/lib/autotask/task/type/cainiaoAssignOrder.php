<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 菜鸟订单号导入任务
 *
 * @author 334395174@qq.com
 * @version 0.1
 */
class financebase_autotask_task_type_cainiaoAssignOrder extends financebase_autotask_task_init
{

    /**
     * 处理
     * @param mixed $task_info task_info
     * @param mixed $error_msg error_msg
     * @return mixed 返回值
     */

    public function process($task_info,&$error_msg)
    {
        if(!is_dir(DATA_DIR.'/financebase/tmp_local')){
            utils::mkdir_p(DATA_DIR.'/financebase/tmp_local');
        }

        $oTask = kernel::single('financebase_data_task');
        $storageLib = kernel::single('taskmgr_interface_storage');
        $this->oFunc->writelog('菜鸟订单号导入任务-开始','settlement','任务ID:'.$task_info['queue_id']);

//        $remote_url = base64_decode($task_info['queue_data']['remote_url']);
        $remote_url = $task_info['queue_data']['remote_url'];
        $local_file = DATA_DIR.'/financebase/tmp_local/'.basename($remote_url);
        $file_type = $task_info['queue_data']['file_type'];
        $page_size = $this->oFunc->getConfig('page_size');
//        $page_size = 3;

        $ioType = kernel::single('financebase_io_'.$file_type);
        $getfile_res = $storageLib->get($remote_url,$local_file);

        list($status,$errmsg) = $oTask->_spliteCainiaoData($local_file,$file_type,$task_info['queue_data']['shop_id'],$task_info, $remote_url,'cainiaoorderimport');

        unlink($local_file); $storageLib->delete($remote_url);

        if($status){
            $this->oFunc->writelog('菜鸟分派任务-完成','settlement','任务ID:'.$task_info['queue_id']);
        }else{
            $error_msg[] = $errmsg; 
            $this->oFunc->writelog('菜鸟分派任务-失败','settlement','任务ID:'.$task_info['queue_id']);
            return false;
        }

        return true;

    }
}