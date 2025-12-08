<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_autotask_task_confirminventory
{

    /**
     * @description 执行批量自动审核
     * @access public
     * @param void
     * @return void
     */
    public function process($params, &$error_msg='') 
    {
        set_time_limit(0);
        
        $log_id = $params['log_id'];
        if(!$log_id || empty($params['log_text'])){
            $error_msg = '数据不完整';
            return false;
        }
        
        $inventoryIds = unserialize($params['log_text']);
        if (empty($inventoryIds) || !is_array($inventoryIds)){
            $error_msg = '缺少ID';
            return false;
        }
        
        $deliBatchLog = app::get('ome')->model('batch_log');
        
        //inventory_id
        $inventoryIds = array_filter($inventoryIds);
        
        #任务处理中
        $rs = $deliBatchLog->update(array('status'=>'2'), array('log_id'=>$log_id, 'status'=>'0'));
        if(is_bool($rs)) {
            $error_msg = '更新失败';
            return false;
        }
        //处理结果
        $result = array('total'=>0, 'succ'=>0, 'fail'=>0);
        
        //审批处理
        foreach ($inventoryIds as $key => $val)
        {
            $result['total']++;
            $result['succ']++;
            $apply_id = intval($val);
            kernel::single('console_inventory_apply')->confirm($apply_id, true);
        }
        
        #任务已处理
        $fail = intval($result['fail']);
        $succ = intval($result['succ']);
        $deliBatchLog->update(array('status'=>'1', 'fail_number'=>$fail, 'succ_number'=>$succ, 'batch_number'=>$result['total']), array('log_id'=>$log_id));
        
        return $result;
    }
}