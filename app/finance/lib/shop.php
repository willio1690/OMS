<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_shop{
    
    /**
     * 获取淘宝已授权的店铺
     * @access public 
     * @param Array $filter 过滤条件
     * @return Array 店铺列表
     */
    function shop_list($filter){
        if (empty($queue_params)){
            $logObj = kernel::single('finance_tasklog');
            $log_title = '请求交易记录数据';
            $log_type = 'trade_search';
            $status = 'success';
            $msg = '队列参数为空';
            $logObj->write_log($log_title,$log_type,$params,$status,$msg);
            return true;
        }
         
        $shop_id = $queue_params['shop_id'];
        $shop_name = $queue_params['shop_name'];
        $node_id = $queue_params['node_id'];
        $start_time = $queue_params['start_time'];
        $end_time = $queue_params['end_time'];
        return $this->request($shop_id,$node_id,$shop_name,$start_time,$end_time,$page=1,$limit=100);
    }

    /**
     * 请求接口数据
     * @param String $shop_id 店铺ID
     * @param String $shop_name 店铺名称
     * @param Int $node_id 节点ID
     * @param String $start_time 开始时间
     * @param String $end_time 结束时间
     * @param Int $page 请求页码
     * @param Int $limit 请求页码记录数
     */
    private function request($shop_id,$node_id,$shop_name,$start_time,$end_time,$page=1,$limit=100,$max=1){
        #防止死循环
        if ($max > 9999){
            return true;
        }

        #组织请求参数
        $api_params = array(
            'start_time' => $start_time,
            'end_time' => $end_time,
            'shop_id' => $shop_id,
            'node_id' => $node_id,
            'shop_name' => $shop_name,
            'page' => $page,
            'limit' => $limit
        );

        #添加日志：运行中
        $logObj = kernel::single('finance_tasklog');
        $log_type = 'trade_search';
        $log_title = '请求['.$shop_name.']交易记录数据:'.$start_time.'-'.$end_time.'';
        $log_id = $logObj->write_log($log_title,$log_type,$api_params,$status='running');
        
        $tradeAPI = kernel::single('finance_rpc_request_trade');
        extract($api_params);

        #请求接口
        $rs = $tradeAPI->trade_search($shop_id,$start_time,$end_time,$page,$limit);
        if ($rs['rsp'] == 'succ'){
            $record_list = $rs['data']['total_records'];
            #添加账单
            $billObj = kernel::single('finance_rpc_response_func_bill');
            $bill_rs = $billObj->batch_trade_add($record_list,$node_id);
            if ($bill_rs['rsp'] != 'succ'){
                $logObj->update_log($log_id,$bill_rs['msg'],$status='fail');
                return false;
            }else{
                #删除日志
                $logObj->delete($log_id);

                #请求剩余数据
                $total_page = $rs['data']['total_pages'];
                if ($total_page > $page){
                    $this->request($shop_id,$node_id,$shop_name,$start_time,$end_time,++$page,$limit,++$max);
                }else{
                    return true;
                }
            }
        }else{
            return false;
        }
    }

}