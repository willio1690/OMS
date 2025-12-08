<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_mdl_tasklog extends dbeav_model{

    var $defaultOrder = array('createtime DESC');

    /**
     * 强制失败
     * @access public
     * @param Array $log_id 日志编号
     * @return bool
     */
    public function abort_fail($log_ids){
        if (empty($log_ids)) return false;

        $log_ids = !is_array($log_ids) ? array($log_ids) : $log_ids;
        $log_id_str = array();
        foreach ($log_ids as $id){
            $log_id_str[] = '\''.$id.'\'';
        }
        if ($log_id_str){
            $log_id_str = implode(',',$log_id_str);
            $sql = "UPDATE `sdb_finance_tasklog` SET `status`='fail' WHERE `log_id` IN (".$log_id_str.") AND `status` NOT IN ('success','fail')";
            $this->db->exec($sql);
        }
        return true;
    }
    
    /**
     * 生成日志编号
     * @access public
     * @return 唯一日志编号
     */
    function gen_id(){
        $microtime = utils::microtime();
        $unique_key = str_replace('.','',strval($microtime));
        $randval = uniqid('', true);
        $unique_key .= strval($randval);
        return md5($unique_key);
    }

    /**
     * 组织重试数据
     * 有单个重试与批量重试，根据重试条件进行组织
     * @param array or int $log_id
     * @param string $log_type 日志分类
     * @param string $retry_type 默认为单个重试，btach:为批量重试
     * @param string $isSelectedAll 是否全选
     * @param string $cursor 当前游标，用于循环选中重试
     */
    function retry($log_id='',$log_type='',$retry_type='', $isSelectedAll='', $cursor='0',$postData=''){

        //单个按钮重试
        $row = $this->db->selectrow("SELECT * FROM `sdb_finance_tasklog` WHERE `log_id`='".$log_id."' AND `status`='fail' ");
        return $this->start_api_retry($row);
    }
    
    /**
     * 调用重试发起API
     * @param array $row 发起重试数据
     * @return 重试的任务名称及状态
     */
    function start_api_retry($row){
        if (empty($row)) return array('task_name'=>'跳过成功任务', 'status'=>'succ', 'msg'=>'');
        
        $params = array(
            'params' => !is_array($row['params']) ? unserialize($row['params']) : $row['params'],
            'log_type' => $row['log_type'],
            'log_id' => $row['log_id'],
            'retry_nums' => $row['retry']+1,
        );

        #更新重试状态及次数
        $this->db->exec("UPDATE sdb_finance_tasklog SET status='retring',retry=retry+1,last_modified='".time()."' WHERE log_id='".$row['log_id']."'");

        $retryObj = kernel::single('finance_cronjob_execQueue');
        $rs = $retryObj->autoretry($params);
        $status = $rs['rsp'];
        $msg = $rs['msg'];
        return array('task_name'=>$row['log_title'], 'status'=>$status, 'msg'=>$msg);
    }

}