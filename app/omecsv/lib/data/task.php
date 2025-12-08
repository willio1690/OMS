<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 任务类
 * Class omecsv_data_task
 */

class omecsv_data_task
{
    
    // 分片数据
    public function _spliteData($file_name, $file_type, $class, $task_name, $task_info)
    {
        $oFunc    = kernel::single('omecsv_func');
        $ioType   = kernel::single('omecsv_io_split_' . $file_type);
        $mdlQueue = app::get('omecsv')->model('queue');
        
        
        $queue_mode = 'import';
        $oProcess   = kernel::single($class);//数据处理类
        
        $page_size = $oProcess->getConfig('page_size');
        
        list($checkRs, $errmsg, $title) = $oProcess->checkFile($file_name, $file_type,$task_info['queue_data']);
        
        if ($checkRs) {
            $ioType->setClass($class);
            $ioType->setWriteFile(true);
            $ioType->setFilePrefix(md5(KV_PREFIX . uniqid() . time()));
            $ioType->setPageSize($page_size);
            $ioType->getData($file_name, 0, -1, 0, true);
            
            if ($ioType->file_data) {
                $storageLib = kernel::single('taskmgr_interface_storage');
                $i          = 1;
                foreach ($ioType->file_data as $local_file) {
                    $move_res = $storageLib->save($local_file, basename($local_file), $remote_url);
                    if ($move_res) {
                        $queueData                = array();
                        $queueData['queue_mode']  = $queue_mode;
                        $queueData['queue_no']    = $task_info['queue_no'];
                        $queueData['create_time'] = time();
                        $queueData['queue_name']  = sprintf("%s_导入任务_%d", $task_name, $i);
                        $queueData['parent_id']   = $task_info['queue_id'];
                        $queueData['split_count'] = 0;
                        $queueData['remote_url']  = $remote_url;
                        $queueData['bill_type']   = $task_info['bill_type'];
                        $queueData['queue_data']  = json_encode(array_merge($task_info['queue_data'], ['title' => $title]));
                        $queue_id                 = $mdlQueue->insert($queueData);
                        
                        omecsv_func::addTaskQueue(array('queue_id' => $queue_id), 'import');
                        
                        $oFunc->writelog('拆分任务-导入队列', 'settlement', "page_" . $i);
                        
                    }
                    $i++;
                    
                    //删除文件
                    unlink($local_file);
                }
            }
            
            return array(true);
        } else {
            $oFunc->writelog('拆分任务-导入队列', 'settlement', $errmsg);
            
            return array(false, $errmsg);
        }
        
    }
    
    
}
