<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
* 费用统计，由API获取平台数据
*
* @category finance
* @package finance/constroller/ananlysis
* @author chenping<chenping@shopex.cn>
* @version $Id: bills.php 2013-10-11 17:23Z
*/
class finance_ctl_analysis_bills extends desktop_controller
{
    /**
     * index
     * @return mixed 返回值
     */

    public function index()
    {
        foreach ($_POST as $k => $v) {
            if (!is_array($v) && $v !== false)
                $_POST[$k] = trim($v);
            if ($_POST[$k] === '') {
                unset($_POST[$k]);
            }
        }

        $this->getAnalysisObject()->set_params($_POST)->display();
    }

    private function getAnalysisObject(){
        
        if($_GET['view'] == 1){
            $obj = kernel::single('finance_analysis_bookbills');
        } elseif($_GET['view'] == 0){
            $obj = kernel::single('finance_analysis_bills');
        }
        return $obj;
    }

    function _views(){
        //$_GET['view_from'] = 1;
        $views = array(
            0 => array(
                'label' => '交易费用',
                'url' => '',
                'optional' => true,
                'addon' => 'tabshow',
            ),
            1 => array(
                'label' => '运营费用',
                'url' => '',
                'optional' => true,
                'addon' => 'tabshow',
            ),
        );
        
        return $views;
    }

    /**
     * bills_get
     * @return mixed 返回值
     */
    public  function  bills_get(){
       
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->display('bill/bills_pop.html');
    }

    /**
     * do_bills_get
     * @return mixed 返回值
     */
    public function do_bills_get(){
        $start_time = date("Y-m-d H:i:s",strtotime($_POST['start_time']));
        $end_time = date("Y-m-d H:i:s",strtotime($_POST['end_time']));
        $page_no = $_POST['page_no'];
        $shop_id = $_POST['shopids'];

        if($shop_id)$shop_id = explode(",", substr($shop_id,0,strlen($shop_id)-1));
        $funcObj = kernel::single('finance_func');
        $shop_list = $funcObj->shop_list(array('node_type'=>'taobao','shop_id|notin'=>$shop_id,'tbbusiness_type'=>'B'));
        $shop_num = count($shop_list);
        $tmp_num = 0;
        if ($shop_list) {
            foreach ($shop_list as $key=>$shop) {
                if(!$shop['node_id']){
                    continue;
                }
                $result = kernel::single('erpapi_router_request')->set('shop', $shop['shop_id'])->finance_bills_get($start_time, $end_time, $page_no, 40, '');

                $analysisBillModel = app::get('finance')->model('analysis_bills');
                // 获取科目
                $format_fee_items = $this->get_fee_items();
                $data = $result['data'];

                if ($result['rsp'] == 'succ') {
                    if ($data['bills']['bill_dto']) {
                        $bills = array();
                        foreach ($data['bills']['bill_dto'] as $value) {
                            $bid = number_format($value['bid'], 0, '', '');
                            $account_id = number_format($value['account_id'], 0, '', '');
                            $bill = array(
                                'bid' => $bid,
                                'account_id' => $account_id,
                                'tid' => (string)$value['tid'],
                                'oid' => (string)$value['oid'],
                                'total_amount' => bcdiv($value['total_amount'], 100, 3),
                                'amount' => bcdiv($value['amount'], 100, 3),
                                'book_time' => $value['book_time'] ? strtotime($value['book_time']) : '',
                                'biz_time' => $value['biz_time'] ? strtotime($value['biz_time']) : '',
                                'pay_time' => $value['pay_time'] ? strtotime($value['pay_time']) : '',
                                'alipay_mail' => $value['alipay_mail'],
                                'obj_alipay_mail' => $value['obj_alipay_mail'],
                                'obj_alipay_id' => $value['obj_alipay_id'],
                                'alipay_outno' => $value['alipay_outno'],
                                'alipay_notice' => $value['alipay_notice'],
                                'status' => $value['status'],
                                'gmt_create' => $value['gmt_create'] ? strtotime($value['gmt_create']) : '',
                                'gmt_modified' => $value['gmt_modified'] ? strtotime($value['gmt_modified']) : '',
                                'num_iid' => $value['num_iid'],
                                'alipay_id' => $value['alipay_id'],
                                'alipay_no' => $value['alipay_no'],
                                'shop_id' => $shop['shop_id'],
                                'shop_type' => $shop['shop_type'],
                                'fee_item_id' => $format_fee_items[$account_id],
                                'finance_type' => bccomp($value['amount'], '0', 3) >= 0 ? '2' : '1',
                            );

                            $exist = $analysisBillModel->getList('bill_id', array('bid' => $bid, 'shop_id' => $shop['shop_id']), 0, 1);
                            if (!$exist) {
                                $bills[] = $bill;
                            }
                        }

                        if ($bills) {
                            $sql = ome_func::get_insert_sql($analysisBillModel, $bills);
                            $analysisBillModel->db->exec($sql);
                        }
                    }
                    if($data['has_next'] == true){
                        $schedule = (100*$page_no)/$data['data']['total_results']*100/$shop_num;
                        echo 'success@'.$schedule."#".$shop_id;
                    }elseif($data['has_next'] == false){
                        $shop_id .=$shop['shop_id'].',';
                    }
                } else {
                    echo '获取失败';
                }
                $tmp_num += $data['data']['total_results'];
            }
            if($tmp_num==$page_no && $data['rsp']=='succ' && $shop_num==$key+1){
                echo 'finish@100';
            }elseif ($data['data']['total_results']>$page_no && $data['rsp']=='succ'){
                $schedule = (100*$page_no)/$data['data']['total_results']*100/$shop_num;
                echo 'success@'.$schedule."#".$shop_id;
            }else{
                echo '获取失败';
            }
        }
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
     * sync_bills_book_get
     * @return mixed 返回值
     */
    public  function  sync_bills_book_get(){
        
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->display('bill/sync_bills_book_get.html');
    }

    /**
     * do_sync_bills_book_get
     * @return mixed 返回值
     */
    public function do_sync_bills_book_get(){
        $start_time = date("Y-m-d H:i:s",strtotime($_POST['start_time']));
        $end_time = date("Y-m-d H:i:s",strtotime($_POST['end_time']));
        $page_no = $_POST['page_no'];
        $shop_id = $_POST['shopids'];
        $bookbillModel = app::get('finance')->model('analysis_book_bills');
        // 获取科目
        $format_fee_items = $this->get_fee_items();
        $funcObj = kernel::single('finance_func');
        if($shop_id)$shop_id = explode(",", substr($shop_id,0,strlen($shop_id)-1));
        $shop_list = $funcObj->shop_list(array('node_type'=>'taobao','shop_id|notin'=>$shop_id,'tbbusiness_type'=>'B'));
        $feeItemModel = app::get('finance')->model('bill_fee_item');
        $feeItemList = $feeItemModel->getList('outer_account_id');
        if ($shop_list) {
            foreach ($feeItemList as $fee_item) {
                if (!$fee_item['outer_account_id']) {
                    continue;
                }
                foreach ($shop_list as $key => $shop) {
                    if(!$shop['node_id']){
                        continue;
                    }
                    $account_id = $fee_item['outer_account_id'];
                    $data = kernel::single('erpapi_router_request')->set('shop', $shop['shop_id'])->finance_sync_bills_book_get($account_id, $start_time, $end_time, null, $page_no, 40);
                    if ($data['rsp'] == 'succ') {
                        if ($data['bills']['book_bill']) {
                            // 数据保存
                            foreach ($data['bills']['book_bill'] as $bill) {
                                $exist = $bookbillModel->getList('book_bill_id', array('bid' => $bill['bid'], 'shop_id' => $shop['shop_id']), 0, 1);
                                if ($exist) {
                                    continue;
                                }

                                $bookbills[] = array(
                                    'bid' => $bill['bid'],
                                    'account_id' => $bill['account_id'],
                                    'journal_type' => $bill['journal_type'],
                                    'amount' => bcdiv($bill['amount'], 100, 3),
                                    'book_time' => strtotime($bill['book_time']),
                                    'description' => $bill['description'],
                                    'gmt_create' => strtotime($bill['gmt_create']),
                                    'shop_id' => $shop['shop_id'],
                                    'shop_type' => $shop['shop_type'],
                                    'fee_item_id' => $format_fee_items[$bill['account_id']],
                                );
                            }

                            if ($bookbills) {
                                $sql = ome_func::get_insert_sql($bookbillModel, $bookbills);
                                $bookbillModel->db->exec($sql);
                            }
                        }
                    } else {
                        echo '获取失败';
                    }
                }
            }
                echo 'finish@100';
        }
    }
}