<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 强制核销任务
 *
 * @author 334395174@qq.com
 * @version 0.1
 */
class financebase_autotask_task_type_forceVerification extends financebase_autotask_task_init
{

    /**
     * 处理
     * @param mixed $task_info task_info
     * @param mixed $error_msg error_msg
     * @return mixed 返回值
     */

    public function process($task_info,&$error_msg)
    {
        $oTask = kernel::single('financebase_data_task');
        $storageLib = kernel::single('taskmgr_interface_storage');
        $this->oFunc->writelog('强制核销任务-开始','settlement','任务ID:'.$task_info['queue_id']);

        $remote_url = $task_info['queue_data']['remote_url'];
        $local_file = DATA_DIR.'/financebase/tmp_local/'.basename($remote_url);
        if (!is_dir(dirname($local_file))) {
            utils::mkdir_p(dirname($local_file));
        }
        
        $file_type = $task_info['queue_data']['file_type'];
        $op_name = $task_info['queue_data']['op_name'] ? $task_info['queue_data']['op_name'] : 'system';

        $mdlBill = app::get('finance')->model('bill');

        $ioType = kernel::single('financebase_io_'.$file_type);
        // $getfile_res = $storageLib->get($remote_url,$local_file);

        
        $data = $ioType->getData($local_file,0,-1);
        $total = count($data);

        // 开始处理
        $db = kernel::database();
        $verification_time = time();

        foreach ($data as $k => $v) {
            if($k > 0)
            {
                if(!$v[1])
                {
                    $error_msg[] = sprintf("第%d行，没有备注",$k+1);
                    continue;
                }

                $bill_info = $mdlBill->getList('bill_id,money,status,bill_bn',array('credential_number'=>$v[0]),0,1);
                if(!$bill_info)
                {
                    $error_msg[] = sprintf("第%d行，流水号不存在",$k+1);
                    continue;
                }

                $bill_info = $bill_info[0];
                if($bill_info['status'] == 2)
                {
                    $error_msg[] = sprintf("第%d行，流水号已核销",$k+1);
                    continue;
                }


                $db->beginTransaction();

                try {
                    $params = array();
                    $params['verification_time'] = $verification_time;
                    $params['status'] = 2;
                    $params['confirm_money'] = $bill_info['money'];
                    $params['unconfirm_money'] = 0;
                    $params['verification_status'] = 3;
                    $params['memo'] = $v[1];
                    if(!$mdlBill->update($params,array('bill_id'=>$bill_info['bill_id'],'status|lthan'=>2)))
                    {
                        throw new Exception('更新应收应退单据表失败');
                    }

                    finance_func::addOpLog($bill_info['bill_bn'],$op_name,'强制核销','核销');


                    $db->commit();
                } catch (Exception $e) {
                    $error_msg[] = sprintf("第%d行，自动对账关联失败原因：",$k+1);
                    $db->rollBack();
                    continue;
                }

            }
        }

        unlink($local_file);
        $storageLib->delete($remote_url);

        return $error_msg ? false : true;

    }
}