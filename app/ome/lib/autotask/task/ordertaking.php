<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_autotask_task_ordertaking
{
    /**
     * @description 订单获取后台处理
     * @access public
     * @param void
     * @return void
     */
    public function process($params, &$error_msg='') 
    {
        $data   = json_decode($params['orderidx'],true);
        $this->_log_id = $log_id = $params['log_id'];
        $this->_sign = $sign = md5('ordertaking_'.$params['orderidx'].$log_id);
        
        $batchLogModel = app::get('ome')->model('batch_log');

        $batchLog = $batchLogModel->dump($log_id);

        $this->__monitor('combine_'.$log_id, json_encode($data), 1);

        if ($batchLog['status'] == '1') return true;

        if ($batchLog['status'] == '0') $batchLogModel->update(array('status'=>'2'),array('log_id'=>$log_id));
        
        if ($data) {
            // 判断是否在执行中
            if ('running' == cachecore::fetch($sign)) {
                return true;
            }
            cachecore::store($sign, 'running',3600);

            set_error_handler(array($this, '_errorHandler'),E_USER_ERROR | E_ERROR);

            // 定位是谁获取的
            if ($batchLog['op_id'] && $batchLog['op_id'] != '16777215') kernel::single('ome_func')->setUser($batchLog['op_id']);

            $combine_data = array();$total_number = $succ_number = $fail_number = 0;
            foreach ($data as $val) {
                if (!$val['idx'] || !$val['hash']) {
                    $fail_number += count($val['orders']); continue;
                }

                $combine_data[] = $val;

                $total_number += count($val['orders']);
            }
            
            if ($combine_data) {
                if(in_array($batchLog['source'], array('split','combineagain'))) {
                    $parentClass = $batchLog['source'];
                }else {
                    $parentClass = 'ordertaking';
                    //订单预处理
                    $preProcessLib = new ome_preprocess_entrance();
                    $preProcessLib->process($combine_data, $msg);
                }
                
                //开始自动确认&&审单
                $orderAuto = new omeauto_auto_combine($parentClass);
                $result = $orderAuto->process($combine_data);
                
                // 处理明细删除被过滤情况
                if ($total_number != $result['total']) {
                    $fail_number += $total_number - $result['total'];
                }
            }

            // 记录一下单次执行的日志
            $memo = array(
                'result' => $result,
                'params' => $data,
            );
            
            $bdlModel = app::get('ome')->model('batch_detail_log');
            $bdl = array(
                'log_id'     => $log_id,
                'createtime' => time(),
                'memo'       => serialize($memo),
                'status'     => 'success',
            );
            $bdlModel->insert($bdl);

            // 会有锁死情况
            $fail_number += (int) $result['fail'];
            if($fail_number > $total_number) {
                $fail_number = $total_number;
            }
            $succ_number = $total_number - $fail_number;
            $sql = 'UPDATE sdb_ome_batch_log SET fail_number=fail_number+'.$fail_number.',succ_number=succ_number+'.$succ_number.' WHERE log_id='.$log_id;

            kernel::database()->exec($sql);
            $sql = 'UPDATE sdb_ome_batch_log SET status="1" WHERE succ_number + fail_number >= batch_number AND log_id='.$log_id;

            kernel::database()->exec($sql);

            cachecore::store($sign, '',1);
        }

        return  true;
    }

    function _errorHandler($errno, $errstr, $errfile, $errline){
        return true;
    }

    /**
     * 监控
     *
     * @return void
     * @author
     **/
    private function __monitor($original_bn,$data,$step)
    {
        $apiLogModel = app::get('ome')->model('api_log');
        $log_id = $apiLogModel->gen_id();
        $kafkaData = array(
            'log_id'      => $log_id,
            'worker'      => $this->_sign,
            'task_name'   => '审单跟踪'.$step,
            'original_bn' => $original_bn,
            'status'      => 'success',
            'createtime'  => time(),
            'api_type'    => 'response',
            'params' => $data,
        );
        
        $apiLogModel->insert($kafkaData);
    }

}
