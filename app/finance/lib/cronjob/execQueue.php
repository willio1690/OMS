<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_cronjob_execQueue{
    
    /**
     * 执行获取交易记录数据队列
     * @access public 
     * @param Array $queue_params 队列数据
     * @return Array 
     */
    function trade_search($cursor_id,$queue_params,$errmsg){
        $rs = array('rsp'=>'succ','msg'=>'');
        if (empty($queue_params)){
            $logObj = kernel::single('finance_tasklog');
            $log_title = '请求交易记录数据';
            $log_type = 'trade_search';
            $status = 'success';
            $msg = '队列参数为空';
            $logObj->write_log($log_title,$log_type,$queue_params,$status,$msg);
            
            $rs['msg'] = $msg;
            return false;
        }

        $node_name = $queue_params['node_name'];
        $node_id = $queue_params['node_id'];
        $start_time = $queue_params['start_time'];
        $end_time = $queue_params['end_time'];
        /*
        $node_name = $queue_params['node_name'];
        $node_id = $queue_params['node_id'];
        $start_time = $queue_params['start_time'];
        $end_time = $queue_params['end_time'];
        $page = isset($queue_params['page']) ? $queue_params['page'] : '1';
        $limit = isset($queue_params['limit']) ? $queue_params['limit'] : '100';
        $is_retry = isset($queue_params['is_retry']) && $queue_params['is_retry'] == 'true' ? true : false;
        $retry_nums = isset($queue_params['retry_nums']) ? $queue_params['retry_nums'] : '1';
        */

        $rs = $this->trade_search_request($node_id,$node_name,$start_time,$end_time);

        return false;
    }

    /**
     * 递归请求交易记录数据
     * @param Int $node_id 节点ID
     * @param String $node_name 节点名称
     * @param String $start_time 开始时间
     * @param String $end_time 结束时间
     * @param Int $page 请求页码
     * @param Int $limit 请求页码记录数
     * @param bool $is_retry 是否重试
     * @param bool $retry_nums 当前重试次数
     * @return Array
     */
    private function trade_search_request($node_id,$node_name,$start_time,$end_time,$page=1,$limit=100,$max=1,$is_retry=false,$retry_nums=1){
        $rs = array('rsp'=>'fail','msg'=>'');

        #防止死循环
        if ($max > 9999){
            $rs['msg'] = '程序异常,执行了:'.$max;
            return $rs;
        }

        #组织请求参数
        $api_params = array(
            'start_time' => $start_time,
            'end_time' => $end_time,
            'node_id' => $node_id,
            'node_name' => $node_name,
            'page' => $page,
            'limit' => $limit
        );

        #添加日志：运行中
        $logObj = kernel::single('finance_tasklog');
        $log_type = 'trade_search';
        $log_title = '请求['.$node_name.']交易记录数据:'.$start_time.'-'.$end_time;
        $addon = array();
        if($is_retry == true){
            $addon['retry'] = $retry_nums;
        }
        $log_id = $logObj->write_log($log_title,$log_type,$api_params,$status='running',$msg='正在请求接口获取数据',$node_id,$addon);
        
        $tradeAPI = kernel::single('finance_rpc_request_trade');
        #请求接口
        $rs = $tradeAPI->trade_search($node_id,$start_time,$end_time,$page,$limit);
        if ($rs['rsp'] == 'succ'){
            $record_list = isset($rs['data']) && $rs['data'] ? $rs['data']['total_records'] : '';
            if ($record_list && is_array($record_list)){
                #添加账单
                $billObj = kernel::single('finance_rpc_response_func_bill');
                $bill_rs = $billObj->batch_trade_add($record_list,$node_id);
                if ($bill_rs['rsp'] != 'succ'){
                    $logObj->update_log($log_id,$bill_rs['msg'],$status='fail');
                    $rs['msg'] = $bill_rs['msg'];
                }else{
                    #删除日志
                    $logObj->delete($log_id);

                    #请求剩余数据
                    $total_page = $rs['data']['total_pages'];
                    if ($total_page > $page){
                        $this->trade_search_request($node_id,$node_name,$start_time,$end_time,++$page,$limit,++$max,$is_retry,$retry_nums);
                    }else{
                        $rs['rsp'] = 'succ';
                    }
                }
            }else{
                #删除日志
                $logObj->delete($log_id);
                $rs['msg'] = '当前时间范围内没有记录';
                $rs['rsp'] = 'succ';
            }
        }else{
            $msg = 'msg_id:'.$rs['msg_id'].'('.$rs['msg'].')-'.$rs['msg_code'];
            $logObj->update_log($log_id,$msg,$status='fail');
            $rs['msg'] = $msg;
        }

        return $rs;
    }

    /**
     * 执行获取交易任务号队列
     * @access public 
     * @param Array $queue_params 队列数据
     * @return Array
     */
    function get_taskid($cursor_id,$queue_params,$errmsg){
        $rs = array('rsp'=>'fail','msg'=>'');

        $logObj = kernel::single('finance_tasklog');
        $log_title = '请求交易任务号';
        $log_type = 'get_taskid';
        if (empty($queue_params)){
            $status = 'success';
            $msg = '队列参数为空';
            $logObj->write_log($log_title,$log_type,$queue_params,$status,$msg);
            $rs['msg'] = $msg;
            return false;
        }
    
        $node_id    = $queue_params['node_id'];
        $node_name  = $queue_params['node_name'];
        $start_time = $queue_params['start_time'];
        $end_time   = $queue_params['end_time'];
        $is_retry   = isset($queue_params['is_retry']) && $queue_params['is_retry'] == 'true' ? true : false;
        $retry_nums = isset($queue_params['retry_nums']) ? $queue_params['retry_nums'] : '1';
        $addon = array();
        if($is_retry == true){
            $addon['retry'] = $retry_nums;
        }

        #添加日志：运行中
        $log_title = '请求['.$node_name.']交易任务号:'.$start_time.'-'.$end_time;
        $log_id = $logObj->write_log($log_title,$log_type,$queue_params,$status='running',$msg='正在请求接口获取数据',$node_id,$addon);
        
        #请求接口
        $tradeAPI = kernel::single('finance_rpc_request_trade');
        $rs = $tradeAPI->trade_taskid_get($node_id,$start_time,$end_time);
        
        if ($rs['rsp'] == 'succ'){
            if(isset($rs['data']) && $rs['data']){
                $taskid = $rs['data']['task_id'];
                $created = $rs['data']['created'];
                #添加到任务号表
                $taskIdObj = kernel::single('finance_taskid');
                if ($taskIdObj->save($taskid,$created,$node_id,$node_name,$start_time,$end_time)){
                    #删除日志
                    $logObj->delete($log_id);
                    
                    $rs['rsp'] = 'succ';
                    $rs['msg'] = '已放入到队列获取任务结果';
                }else{
                    $logObj->update_log($log_id,$msg='任务号存储失败',$status='fail');
                    $rs['msg'] = $msg;
                }
            }else{
                #删除日志
                $logObj->delete($log_id);
                $rs['msg'] = '当前时间范围内没有记录';
                $rs['rsp'] = 'succ';
            }
        }else{
            $msg = 'msg_id:'.$rs['msg_id'].'('.$rs['msg'].')-'.$rs['msg_code'];
            $logObj->update_log($log_id,$msg,$status='fail');
            $rs['msg'] = $msg;
        }
        
        return false;
    }

    /**
     * 自动重试队列执行
     * @access public 
     * @param Array $queue_params 队列数据
     * @return Array 
     */
    function autoretry($cursor_id,$queue_params,$errmsg){
        $rs = array('rsp'=>'succ','msg'=>'');
        $logObj = kernel::single('finance_tasklog');
        if (empty($queue_params)){
            $log_title = '自动重试';
            $log_type = 'other';
            $status = 'success';
            $msg = '队列参数为空';
            $logObj->write_log($log_title,$log_type,$queue_params,$status,$msg);
            
            $rs['msg'] = $msg;
            return false;
        }
         
        $log_type = $queue_params['log_type'];
        $log_id = $queue_params['log_id'];
        $params = $queue_params['params'];
        $params['is_retry'] = 'true';
        $params['retry_nums'] = $queue_params['retry_nums'];
        switch($log_type){
            case 'trade_search':#实时交易数据请求
                $rs = $this->trade_search($cursor_id,$params,$errmsg);
                #删除重试的日志:因失败的话会创建一条新日志,TODO:这是为了复用程序代码
                $logObj->delete($log_id);
                break;
            case 'get_taskid':#请求任务号
                $rs = $this->get_taskid($cursor_id,$params,$errmsg);
                #删除重试的日志:因失败的话会创建一条新日志,TODO:这是为了复用程序代码
                $logObj->delete($log_id);
                break;
            case 'get_taskid_result':#请求任务号结果
                $task_id = $params['task_id'];
                $node_id = $params['node_id'];
                $node_name = $params['node_name'];
                $start_time = $params['start_time'];
                $end_time = $params['end_time'];
                $apiObj = kernel::single('finance_apitrade');
                $rs = $apiObj->get_taskid_result($task_id,$node_id,$node_name,$start_time,$end_time);
                if ($rs['rsp'] == 'succ'){
                    #删除日志
                    $logObj->delete($log_id);
                }else{
                    #更新日志
                    $logObj->update_log($log_id,$msg=$rs['msg'],$status='fail');
                }
                break;
        }
        
        return false;
    }


    /**
     * runQueue
     * @return mixed 返回值
     */
    public function runQueue() 
    {
        $h = date('H');
        if($h < 18 && $h>6) return;

        $queueModel = app::get('base')->model('queue');
        $queueList = $queueModel->db->select('select queue_id from sdb_base_queue limit '.$queueModel->limit);
        foreach($queueList as $r){
            $_POST['task_id'] = $r['queue_id'];
            kernel::single('base_service_queue')->worker();
        }
    }

    /**
     * bills_get
     * @param mixed $cursor_id ID
     * @param mixed $queue_params 参数
     * @param mixed $errmsg errmsg
     * @return mixed 返回值
     */
    public function bills_get($cursor_id,$queue_params,$errmsg)
    {
        $rs = array('rsp'=>'succ','msg'=>'');
        if (empty($queue_params)){
            $logObj = kernel::single('finance_tasklog');
            $log_title = '请求账单数据';
            $log_type = 'bills_get';
            $status = 'success';
            $msg = '队列参数为空';
            $logObj->write_log($log_title,$log_type,$queue_params,$status,$msg);
            
            $rs['msg'] = $msg;
            return false;
        }

        // $node_name  = $queue_params['node_name'];
        // $node_id    = $queue_params['node_id'];
        // $start_time = $queue_params['start_time'];
        // $end_time   = $queue_params['end_time'];
        // $shop_id    = $queue_params['shop_id'];
        
        $time_type = array('1','2');
        foreach ($time_type as $key => $type) {
            $queue_params['page_no'] = 1;
            $queue_params['page_size'] = 50;
            $queue_params['time_type'] = $type;
         
            $this->retry_bill_get('',$queue_params,$errmsg);

        }

        return false;
    }

    private function get_fee_items()
    {
        static $format_fee_items;
        if ($format_fee_items) {
            return $format_fee_items;
        }

        $feeItemModel = app::get('finance')->model('bill_fee_item');
        $fee_items = $feeItemModel->getList('outer_account_id,fee_item_id',array('channel' => 'tmall'));
        foreach ((array) $fee_items as $value) {
            $format_fee_items[$value['outer_account_id']] = $value['fee_item_id'];
        }

        return $format_fee_items;
    }

    /**
     * retry_bill_get
     * @param mixed $cursor_id ID
     * @param mixed $queue_params 参数
     * @param mixed $errmsg errmsg
     * @return mixed 返回值
     */
    public function retry_bill_get($cursor_id,$queue_params,&$errmsg)
    {  
        if (!$queue_params) {
            return false;
        }
        set_time_limit(0);

        $analysisBillModel = app::get('finance')->model('analysis_bills');
        // 获取科目
        $format_fee_items = $this->get_fee_items();

        $funcObj = kernel::single('finance_func');

        $node_name  = $queue_params['node_name'];
        $node_id    = $queue_params['node_id'];
        $start_time = $queue_params['start_time'];
        $end_time   = $queue_params['end_time'];
        $shop_id    = $queue_params['shop_id'];  
        $page_no    = $queue_params['page_no'];
        $page_size  = $queue_params['page_size'];
        $time_type  = $queue_params['time_type'];
        $worker = 'finance_cronjob_execQueue.retry_bill_get';

        $shop = $funcObj->getShopByShopID($shop_id);
        $log_title = '请求账单数据:' . $start_time . '至' . $end_time . '';
        //do {
            $result = kernel::single('erpapi_router_request')->set('shop', $shop_id)->finance_bills_get($start_time,$end_time,$page_no,$page_size,$time_type);
            $data = $result['data'];

            if ($result['rsp'] == 'succ') {
                if ($data['bills']['bill_dto']) {
                    $bills = array();
                    foreach ($data['bills']['bill_dto'] as $value) {
                        $bid = number_format($value['bid'],0,'','');
                        $account_id = number_format($value['account_id'],0,'','');
                        $bill = array(
                            'bid'             => $bid,
                            'account_id'      => $account_id,
                            'tid'             => (string) $value['tid'],
                            'oid'             => (string) $value['oid'],
                            'total_amount'    => bcdiv($value['total_amount'],100,3),
                            'amount'          => bcdiv($value['amount'],100,3),
                            'book_time'       => $value['book_time'] ? strtotime($value['book_time']) : '',
                            'biz_time'        => $value['biz_time'] ? strtotime($value['biz_time']): '',
                            'pay_time'        => $value['pay_time'] ? strtotime($value['pay_time']) : '',
                            'alipay_mail'     => $value['alipay_mail'],
                            'obj_alipay_mail' => $value['obj_alipay_mail'],
                            'obj_alipay_id'   => $value['obj_alipay_id'],
                            'alipay_outno'    => $value['alipay_outno'],
                            'alipay_notice'   => $value['alipay_notice'],
                            'status'          => $value['status'],
                            'gmt_create'      => $value['gmt_create'] ? strtotime($value['gmt_create']) : '',
                            'gmt_modified'    => $value['gmt_modified'] ? strtotime($value['gmt_modified']) : '',
                            'num_iid'         => $value['num_iid'],
                            'alipay_id'       => $value['alipay_id'],
                            'alipay_no'       => $value['alipay_no'],
                            'shop_id'         => $shop_id,
                            'shop_type'       => $shop['shop_type'],
                            'fee_item_id'     => $format_fee_items[$account_id],
                            'finance_type'    => bccomp($value['amount'], '0',3)>=0 ? '2' : '1',
                        );

                        $exist = $analysisBillModel->getList('bill_id',array('bid'=>$bid,'shop_id' => $shop_id),0,1);
                        if (!$exist) {
                            $bills[] = $bill;
                        }
                    }

                    if ($bills) {
                        $sql = ome_func::get_insert_sql($analysisBillModel,$bills);
                        $analysisBillModel->db->exec($sql);
                    }
                }

                if($data['has_next'] == true){
                    $new_queue_params = $queue_params;
                    $new_queue_params['page_no'] = $queue_params['page_no'] + 1;

                    $funcObj->addTask($log_title,$worker,$new_queue_params,$type='slow');
                }
            } else {
                // 请求失败:进队列重新请求
                $new_queue_params = $queue_params;
                $new_queue_params['page_no'] = $page_no;

                //$funcObj->addTask($log_title,$worker,$new_queue_params,$type='slow');
                //break;
            }

         //   $page_no++;
        //} while ($data['has_next'] == true);

        return false;
    }

    /**
     * book_bills_get
     * @param mixed $cursor_id ID
     * @param mixed $queue_params 参数
     * @param mixed $errmsg errmsg
     * @return mixed 返回值
     */
    public function book_bills_get($cursor_id,$queue_params,$errmsg)
    {
        if (!$queue_params) {
            return false;
        }

        $start_time = $queue_params['start_time'];
        $end_time   = $queue_params['end_time'];
        $shop_id    = $queue_params['shop_id'];  
        $page_no    = $queue_params['page_no'];
        $page_size  = $queue_params['page_size'];
        $account_id  = $queue_params['account_id'];

        kernel::single('finance_rpc_request_bill')->setShopId($shop_id)->bills_book_get($account_id,$start_time,$end_time,null,$page_no,$page_size);

        return false;
    }

    /**
     * sync_book_bills_get
     * @param mixed $cursor_id ID
     * @param mixed $queue_params 参数
     * @param mixed $errmsg errmsg
     * @return mixed 返回值
     */
    public function sync_book_bills_get($cursor_id,$queue_params,$errmsg)
    {  
        if (!$queue_params) {
            return false;
        }

        $bookbillModel = app::get('finance')->model('analysis_book_bills');

        // 获取科目
        $format_fee_items = $this->get_fee_items();

        $funcObj = kernel::single('finance_func');

        $node_name  = $queue_params['node_name'];
        $node_id    = $queue_params['node_id'];
        $start_time = $queue_params['start_time'];
        $end_time   = $queue_params['end_time'];
        $shop_id    = $queue_params['shop_id'];  
        $page_no    = $queue_params['page_no'];
        $page_size  = $queue_params['page_size'];
        $account_id  = $queue_params['account_id'];

        $worker = 'finance_cronjob_execQueue.sync_book_bills_get';

        $shop = $funcObj->getShopByShopID($shop_id);
        $log_title = '请求虚拟账户明细数据:' . $start_time . '至' . $end_time . '';

        $result = kernel::single('erpapi_router_request')->set('shop', $shop_id)->finance_sync_bills_book_get($account_id,$start_time,$end_time,null,$page_no,$page_size);

        $data = $result['data'];

        if ($result['rsp'] == 'succ') {
            if($data['bills']['book_bill']){
                // 数据保存
                foreach ($data['bills']['book_bill'] as $bill) {
                    $exist = $bookbillModel->getList('book_bill_id',array('bid'=>$bill['bid'],'shop_id' => $shop['shop_id']),0,1);
                    if ($exist) { continue;}

                    $bookbills[] = array(
                        'bid'                 => $bill['bid'],
                        'account_id'          => $bill['account_id'],
                        'journal_type'        => $bill['journal_type'],
                        'amount'              => bcdiv($bill['amount'], 100,3),
                        'book_time'           => strtotime($bill['book_time']),
                        'description'         => $bill['description'],
                        'gmt_create'          => strtotime($bill['gmt_create']),
                        'shop_id'             => $shop['shop_id'],
                        'shop_type'           => $shop['shop_type'],
                        'fee_item_id'         => $format_fee_items[$bill['account_id']],
                    );
                }

                if ($bookbills) {
                    $sql = ome_func::get_insert_sql($bookbillModel,$bookbills);
                    $bookbillModel->db->exec($sql);
                }
            }

            if($data['has_next'] == true){
                $new_queue_params = $queue_params;
                $new_queue_params['page_no'] = $queue_params['page_no'] + 1;

                $funcObj->addTask($log_title,$worker,$new_queue_params,$type='slow');
            }

        } else {
            // 请求失败:进队列重新请求
            $new_queue_params = $queue_params;
            $new_queue_params['page_no'] = $page_no;

            //$funcObj->addTask($log_title,$worker,$new_queue_params,$type='slow');
        }

        return false;
    }
}