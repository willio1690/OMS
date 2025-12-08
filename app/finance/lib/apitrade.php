<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_apitrade{
    
    /**
     * 获取交易任务号结果
     * @access public 
     * @param String $task_id 任务号
     * @param String $node_id 节点ID
     * @param String $node_name 节点名称
     * @param DateTime $start_time 交易开始时间
     * @param DateTime $end_time 交易结束时间
     * @return void
     */
    function get_taskid_result($task_id,$node_id,$node_name='',$start_time,$end_time){
        $result = array('rsp'=>'fail','msg'=>'','msg_code'=>'');
        if (empty($task_id)){
            $result['msg'] = '任务号不能为空';
            return $result;
        }

        #请求接口
        $tradeAPI = kernel::single('finance_rpc_request_trade');
        $rs = $tradeAPI->trade_taskresult_get($node_id,$task_id);
        if ($rs['rsp'] == 'succ'){
            $download_url = isset($rs['data']) && $rs['data'] ? $rs['data']['download_url'] : '';
            if (!empty($download_url)){
                if(PHP_OS != 'WINNT'){
                    $save_path = '/tmp/alipay_trade/'.date('YmdHis').rand(1,6).rand(1,6).'-'.$task_id.'.csv';
                }
                $save_path = finance_download::download_file($download_url,$save_path,$msg);
                if ($save_path){
                    $analyse_result = $this->analyse_file($save_path,$node_id);
                    if ($analyse_result['rsp'] == 'fail'){
                        $result['msg'] = $analyse_result['msg'];
                    }else{
                        $result['rsp'] = 'succ';
                    }
                    #删除本地数据文件
                    //finance_download::rm_file($save_path);
                }else{
                    $result['msg'] = '文件下载失败:'.$download_url.',错误原因:'.$msg;
                }
            }else{
                $result['rsp'] = 'succ';

                #任务号过期，重新生成获取任务号的队列
                if($rs['msg_code'] == 'expired'){
                    $funcObj = kernel::single('finance_func');
                    $worker = 'finance_cronjob_execQueue.get_taskid';
                    $log_title = '请求['.$node_name.']交易任务号:'.$start_time.'至'.$end_time;
                    $log_params = array(
                        'task_id' => $task_id,
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'node_id' => $node_id,
                        'node_name' => $node_name,
                    );
                    if(!$funcObj->addTask($log_title,$worker,$log_params,$type='slow')){
                        #任务创建失败,则添加到重试日志
                        $logObj = kernel::single('finance_tasklog');
                        $log_type = 'get_taskid';
                        $logObj->write_log($log_title,$log_type,$log_params,$status='fail',$msg='添加队列失败');
                    }
                }else{
                    $result['msg'] = '任务号结果为空';
                }
            }
        }else{
            if ($rs['res'] == 'w01107' || $rs['res'] == 'W90019') {
                $result['rsp'] = 'succ';
            }

            $result['msg'] = 'msg_id:'.$rs['msg_id'].'('.$rs['msg'].')-'.$rs['msg_code'];
        }
        
        $result['msg_code'] = isset($rs['msg_code']) ? $rs['msg_code'] : '';
        return $result;
    }

    /**
     * 下载文件分析
     * 分析下载成功的文件:每100条进行存储到账单
     * @access private 
     * @param String $save_path 文件路径
     * @param String $node_id 节点ID
     * @return Array
     */
    private function analyse_file($save_path,$node_id){
        $result = array('rsp'=>'succ','msg'=>'');
        $billObj = kernel::single('finance_rpc_response_func_bill');
        
        $save_path_arr = array(
            'transfer' => str_replace('.csv','_transfer.csv',$save_path),
            'charge' => str_replace('.csv','_charge.csv',$save_path),  
        );
        foreach ($save_path_arr as $type=>$path){
            $fp = fopen($path,'rb');
            if (!$fp) {
                $result['rsp'] = 'fail';
                $result['msg'] = '打开本地文件:'.$path.'失败';
                return $result;
            }
            $i = 1;
            $record = $content = array();
            $csv_title = array();
            $title_flag = false;
            while($fp && !feof($fp)){
                if ($title_flag == false){
                    $csv_title = array_flip(fgetcsv($fp));
                    $title_flag = true;
                    continue;#去除第一行标题
                }
                if($i >= 100){
                    #批量添加账单
                    $bill_rs = $billObj->batch_trade_add($record,$node_id);
                    if ($bill_rs['rsp'] != 'succ'){#失败
                        $result['msg'] = $bill_rs['msg'];
                        $result['rsp'] = 'fail';
                        return $result;
                    }else{#成功
                        $i = 1;
                        $record = NULL;
                    }
                }
                $content = fgetcsv($fp);
                if ($content){
                    $type = $content[$csv_title['type']];
                    $business_type = $content[$csv_title['business_type']];
                    $order_type = '';
                    $order_status = '';
                    if ($type == 'transfer' && $business_type == 'transfer_01'){
                        $order_type = 'TRADE';#销售收款
                        $order_status = 'TRADE_FINISHED';
                        $order_from = 'TAOBAO';
                    }elseif ($type == 'charge' && $business_type == 'charge_01'){
                        $order_type = 'CHARGE';#信用卡手续费
                        $order_status = 'CHARGE_FINISHED';
                        $order_from = 'ALIPAY';
                    }
                    if (!$order_type) continue;

                    $in_amount = $content[$csv_title['in_amount']];
                    $out_amount = $content[$csv_title['out_amount']];
                    if(!empty($in_amount)){
                        $total_amount = abs($in_amount);
                        $in_out_type = 'in';
                    }else{
                        $total_amount = abs($out_amount);
                        $in_out_type = 'out';
                    }
                    $total_amount = !empty($in_amount) ? $in_amount : $out_amount;
                    $tmp_sdf = array(
                        'alipay_order_no' => $content[$csv_title['alipay_order_no']],
                        'merchant_order_no' => $content[$csv_title['merchant_order_no']],
                        'order_type' => $order_type,
                        'order_from' => $order_from,
                        'order_status' => $order_status,
                        'order_title' => $content[$csv_title['memo']],
                        'total_amount' => abs($total_amount),
                        'in_out_type' => $in_out_type,
                        'modified_time' => $content[$csv_title['create_time']],
                        'opposite_user_id' => $content[$csv_title['opt_user_id']],
                        'balance' => $content[$csv_title['balance']],
                    );
                    $num++;
                    $record[] = $tmp_sdf;
                }
                $i++;
            }

            fclose($fp);

            if ($record){#存储剩余内容
                $bill_rs = $billObj->batch_trade_add($record,$node_id);
                if ($bill_rs['rsp'] != 'succ'){
                    $result['msg'] = $bill_rs['msg'];
                    $result['rsp'] = 'fail';
                    return $result;
                }else{
                    $record = NULL;
                }
            }
        }
        return $result;
    }

}