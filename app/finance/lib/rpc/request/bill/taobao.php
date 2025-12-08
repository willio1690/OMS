<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
* 财务费用
*
* @category finance
* @package finance/lib/rpc/request/bill
* @author chenping<chenping@shopex.cn>
* @version $Id: taobao.php 2013-10-11 17:23Z
*/
class finance_rpc_request_bill_taobao extends finance_rpc_request_bill_abstract
{
    /**
     * 财务科目
     * 
     * @return void
     * @author 
     * */

    public function bill_account_get($account_id = array())
    {
        $rs = array('rsp'=>'fail','msg'=>'','msg_code'=>'','msg_id'=>'','data'=>'');

        $denytime = array(
            0 => array(mktime(9,30,0,date('m'),date('d'),date('Y')),mktime(11,0,0,date('m'),date('d'),date('Y'))),
            1 => array(mktime(14,0,0,date('m'),date('d'),date('Y')),mktime(17,0,0,date('m'),date('d'),date('Y'))),
            2 => array(mktime(20,0,0,date('m'),date('d'),date('Y')),mktime(22,30,0,date('m'),date('d'),date('Y'))),
            2 => array(mktime(1,0,0,date('m'),date('d'),date('Y')),mktime(3,0,0,date('m'),date('d'),date('Y'))),
        );

        $now = time();
        foreach ($denytime as $value) {
            if ($value[0]<=$now && $now<=$value[1]) {
                $rs['msg'] = 'deny time';
                // return $rs;
            }
        }

        $api_name = 'store.bill.accounts.get';

        $params = array(
            'fields' => 'account_id,account_code,account_name,account_type,related_order,gmt_create,gmt_modified,status',
        );

        if ($account_id) {
            $params['aids'] = implode(',', $account_id);
        }

        $title = '店铺[' . $this->shop['name'] . ']获取财务科目';
        $callback = array(
            'class' => get_class($this),
            'method' => 'bill_account_get_callback',
        );
        $return = $this->_caller->request($api_name,$params,$callback,$title,$this->shop['shop_id'],10,false);

        $rs['rsp'] = 'succ';
        return $rs;
    }

    private $outer_account_id = array('3200052031','3200053031','3200013031','3200058031','3200060031','3200059031','3200061031','3200011031','3200063031','3200036031','3200065031','3200066031','3210085031','3200038031','3200062031','3200102041','3200034031','3200050031','3200037031','3200030031','3200021031','3200039031','3122765031','3200084031','3200084031','3200032031','3200027031','3200045031','3200031031');
        /**
     * bill_account_get_callback
     * @param mixed $result result
     * @return mixed 返回值
     */
    public function bill_account_get_callback($result)
    {
        $accounts = $result->get_data();

        $feeTypeModel = app::get('finance')->model('bill_fee_type');
        $feeItemModel = app::get('finance')->model('bill_fee_item');
        foreach ((array) $accounts['accounts']['account'] as $account) {
            // 判断科目类型
            $fee_type = $feeTypeModel->dump(array('outer_account_type' => $account['account_type']));
            if (!$fee_type) continue;

            // 判断科目是否存在
            $fee_item = $feeItemModel->dump(array('fee_item_code'=>$account['account_code'],'channel' => 'tmall'));
            $item = array(
                'fee_item_id'   => $fee_item ? $fee_item['fee_item_id'] : null,
                'fee_type_id'   => $fee_type['fee_type_id'],
                'fee_item'      => $account['account_name'],
                'inlay'         => 'true',
                'channel'       => 'tmall',
                'createtime'    => time(),
                'fee_item_code' => $account['account_code'],
                'outer_account_id'    => $account['account_id'],
                'related_order' => ($fee_type['fee_type_id'] == '1' || in_array($account['account_id'],$this->outer_account_id)) ? 'true' : 'false',
            );

            $feeItemModel->save($item);
        }

        return $this->_caller->callback($result);
    }

    /**
     * 费用明细
     * 
     * @return void
     * @author 
     * */
    public function bills_get($start_time,$end_time,$page_no=1,$page_size=40,$time_type='')
    {
        $rs = array('rsp'=>'fail','msg'=>'','msg_code'=>'','msg_id'=>'','data'=>'');

        $denytime = array(
            0 => array(mktime(9,30,0,date('m'),date('d'),date('Y')),mktime(11,0,0,date('m'),date('d'),date('Y'))),
            1 => array(mktime(14,0,0,date('m'),date('d'),date('Y')),mktime(17,0,0,date('m'),date('d'),date('Y'))),
            2 => array(mktime(20,0,0,date('m'),date('d'),date('Y')),mktime(22,30,0,date('m'),date('d'),date('Y'))),
            2 => array(mktime(1,0,0,date('m'),date('d'),date('Y')),mktime(3,0,0,date('m'),date('d'),date('Y'))),
        );

        $now = time();
        foreach ($denytime as $value) {
            if ($value[0]<=$now && $now<=$value[1]) {
                $rs['msg'] = 'deny time';
                // return $rs;
            }
        }

        $api_name = 'store.bills.get';

        $params = array(
            'start_time' => $start_time,
            'end_time' => $end_time,
            'page_no' => $page_no,
            'page_size' => $page_size,
            'time_type' => $time_type,
        );

        $title = '店铺[' . $this->shop['name'] . ']获取账单明细';
        $logModel = app::get(self::_APP_NAME)->model('api_log');
        $log_id = $logModel->gen_id();
        $logModel->write_log($log_id,$title,get_class($this->_caller),'call',array($api_name,$params,$this->shop['shop_id'],10),'','request','running');

        $result = $this->_caller->call($api_name,$params,$this->shop['shop_id'],10);
 
        if ($result->res_ltype > 0) {
            for ($i=0;$i<3;$i++) {
                $result = $this->_caller->call($api_name,$params,$this->shop['shop_id'],10);
                if ($result->res_ltype == 0) {
                    break;
                }
            }
        }
        $rs['msg_id'] = $result->msg_id;

        // 记日志
        $api_status = $result->rsp != 'succ' ? 'fail' : 'success';
        $logData = array(
            'msg_id' => $result->msg_id,
            'status' => $api_status,
        );
        $logModel->update($logData,array('log_id'=>$log_id));

        if ($result === false) {
            $rs['msg'] = '请求失败';
            return $rs;
        } elseif ($result->rsp !== 'succ') {
            $rs['msg'] = $result->err_msg;
            return $rs;
        }

        $rs['rsp']  = 'succ';
        $rs['data'] = json_decode($result->data,true);
        return $rs;
    }
    
        /**
     * sync_bills_book_get
     * @param mixed $account_id ID
     * @param mixed $start_time start_time
     * @param mixed $end_time end_time
     * @param mixed $journal_types journal_types
     * @param mixed $page_no page_no
     * @param mixed $page_size page_size
     * @return mixed 返回值
     */
    public function sync_bills_book_get($account_id,$start_time,$end_time,$journal_types = '',$page_no = 1,$page_size = 40){
        $rs = array('rsp'=>'fail','msg'=>'','msg_code'=>'','msg_id'=>'','data'=>'');

        $api_name = 'store.bill.book.bills.get';

        $params = array(
            'account_id' => $account_id,
            'start_time' => $start_time,
            'end_time'   => $end_time,
            'page_no'    => $page_no,
            'page_size'  => $page_size,
        );
        if ($journal_types) {
            $params['journal_types'] = $journal_types;
        }

        $title = '店铺[' . $this->shop['name'] . ']获取虚拟账户明细数据';

        $logModel = app::get(self::_APP_NAME)->model('api_log');
        $log_id = $logModel->gen_id();
        $logModel->write_log($log_id,$title,get_class($this->_caller),'call',array($api_name,$params,$this->shop['shop_id'],10),'','request','running');

        $result = $this->_caller->call($api_name,$params,$this->shop['shop_id'],10);
 
        if ($result->res_ltype > 0) {
            for ($i=0;$i<3;$i++) {
                $result = $this->_caller->call($api_name,$params,$this->shop['shop_id'],10);
                if ($result->res_ltype == 0) {
                    break;
                }
            }
        }
        $rs['msg_id'] = $result->msg_id;

        // 记日志
        $api_status = $result->rsp != 'succ' ? 'fail' : 'success';
        $logData = array(
            'msg_id' => $result->msg_id,
            'status' => $api_status,
        );
        $logModel->update($logData,array('log_id'=>$log_id));

        if ($result === false) {
            $rs['msg'] = '请求失败';
            return $rs;
        } elseif ($result->rsp !== 'succ') {
            $rs['msg'] = $result->err_msg;
            return $rs;
        }

        $rs['rsp']  = 'succ';
        $rs['data'] = json_decode($result->data,true);
        return $rs;
    }
    
    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function bills_book_get($account_id,$start_time,$end_time,$journal_types = '',$page_no = 1,$page_size = 40)
    {
        $rs = array('rsp'=>'fail','msg'=>'','msg_code'=>'','msg_id'=>'','data'=>'');

        $api_name = 'store.bill.book.bills.get';

        $params = array(
            'account_id' => $account_id,
            'start_time' => $start_time,
            'end_time'   => $end_time,
            'page_no'    => $page_no,
            'page_size'  => $page_size,
        );
        if ($journal_types) {
            $params['journal_types'] = $journal_types;
        }

        $title = '店铺[' . $this->shop['name'] . ']获取虚拟账户明细数据';
        $callback = array(
            'class' => get_class($this),
            'method' => 'bills_book_get_callback',
        );
        $return = $this->_caller->request($api_name,$params,$callback,$title,$this->shop['shop_id'],10,false);

        $rs['rsp'] = 'succ';
        return $rs;
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function bills_book_get_callback($result)
    {
        $status          = $result->get_status();
        $data            = $result->get_data();
        $request_params  = $result->get_request_params();
        $callback_params = $result->get_callback_params();

        if ($status == 'succ' && $data['bills']['book_bill']) {
            $funcObj = kernel::single('finance_func');

            $shop = $funcObj->getShopByShopID($callback_params['shop_id']);

            $bookbillModel = app::get('finance')->model('analysis_book_bills');

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
                    // 'taobao_alipay_id' => ,
                    // 'other_alipay_id'  => ,
                    'shop_id'             => $shop['shop_id'],
                    'shop_type'           => $shop['shop_type'],
                    'fee_item_id'         => $this->get_fee_item($bill['account_id']),
                );
            }

            if ($bookbills) {
                $sql = ome_func::get_insert_sql($bookbillModel,$bookbills);
                $bookbillModel->db->exec($sql);
            }

            if ($data['has_next'] == true) {
                // 放队列
                $worker = 'finance_cronjob_execQueue.book_bills_get';

                $params = $request_params;
                $params['page_no'] += 1;
                $params['shop_id'] = $shop['shop_id'];

                $log_title = '请求获取虚拟账户明细:'.$params['start_time'].'至'.$params['end_time'].'';
                $funcObj->addTask($log_title,$worker,$params,$type='slow');
            }
        }
        return $this->_caller->callback($result);
    }

    private function get_fee_item($outer_id)
    {
        static $fee_item;

        if ($fee_item[$outer_id]) {
            return $fee_item[$outer_id];
        }

        $feeItemModel = app::get('finance')->model('bill_fee_item');

        $feeItem = $feeItemModel->getList('fee_item_id',array('outer_account_id'=>$outer_id,'channel' => 'tmall'),0,1);

        $fee_item[$outer_id] = $feeItem[0]['fee_item_id'];

        return $fee_item[$outer_id];
    }
}