<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/12/1 13:09:13
 * @describe: 类
 * ============================
 */
class financebase_data_bill_businesstype_cainiao extends financebase_data_bill_businesstype_abstract {
    protected $modelName = 'cainiao';

    protected function confirmItem($bill) {
        $model = '';
        $rs = $this->confirmItemModel($bill, $model);
        if(is_object($model) && $rs['data']['import_id']) {
            $importModel = app::get('financebase')->model('bill_import');
            $nrn = $model->count(array('confirm_account'=>'0','import_id'=>$rs['data']['import_id']));
            $mun = $model->count(array('confirm_account'=>'1','import_id'=>$rs['data']['import_id']));
            $importModel->update(array('not_reconciliation_num'=>$nrn,'money_unequal_num'=>$mun), array('id'=>$rs['data']['import_id']));
        }
        return $rs;
    }

    protected function confirmItemModel($bill, &$model, $confirm_account = '0') {
        $return = array('rsp'=>'fail', 'msg'=>'未匹配到');
        $filter = array(
            'pay_serial_number' => array($bill['trade_no'], $bill['out_trade_no']),
            'confirm_status' => '1',
            'split_status' => '0',
            'confirm_account' => $confirm_account,
        );
        //订单号导入
        $model = app::get('financebase')->model('bill_import_order');
        $rows = $model->getList('*', $filter);
        if($rows) {
            $sum = 0;
            $ids = array();
            foreach ($rows as $v) {
                $ids[] = $v['id'];
                $sum = bcadd($v['expenditure_money'], $sum, 2);
            }
            $v['ids'] = $ids;
            $sum = bcadd($sum, $bill['money'], 2);
            if($sum == 0) {
                $model->update(array('confirm_account'=>'2'), array('id'=>$ids));
                return array('rsp'=>'succ', 'data'=>$v, 'type'=>'order');
            }
            $model->update(array('confirm_account'=>'1'), array('id'=>$ids));
            $return['msg'] = '金额不对';
            $return['data'] = $v;
            return $return;
        }
        //sku导入
        $model = app::get('financebase')->model('bill_import_sku');
        $rows = $model->getList('*', $filter);
        if($rows) {
            $sum = 0;
            $ids = array();
            foreach ($rows as $v) {
                $ids[] = $v['id'];
                if($sum == 0) {
                    $addBmid = $v['bm_id'];
                    $sum = bcadd($v['expenditure_money'], $sum, 2);
                } elseif($v['bm_id'] == $addBmid) {
                    $sum = bcadd($v['expenditure_money'], $sum, 2);
                }
            }
            $v['ids'] = $ids;
            $sum = bcadd($sum, $bill['money'], 2);
            if($sum == 0) {
                $model->update(array('confirm_account'=>'2'), array('id'=>$ids));
                return array('rsp'=>'succ', 'data'=>$v, 'type'=>'sku');
            }
            $model->update(array('confirm_account'=>'1'), array('id'=>$ids));
            $return['msg'] = '金额不对';
            $return['data'] = $v;
            return $return;
        }
        //销售周期导入
        $model = app::get('financebase')->model('bill_import_sale');
        $rows = $model->getList('*', $filter);
        if($rows) {
            $sum = 0;
            $ids = array();
            foreach ($rows as $v) {
                $ids[] = $v['id'];
                $sum = bcadd($v['expenditure_money'], $sum, 2);
            }
            $v['ids'] = $ids;
            $sum = bcadd($sum, $bill['money'], 2);
            if($sum == 0) {
                $model->update(array('confirm_account'=>'2'), array('id'=>$ids));
                return array('rsp'=>'succ', 'data'=>$v, 'type'=>'sale');
            }
            $model->update(array('confirm_account'=>'1'), array('id'=>$ids));
            $return['msg'] = '金额不对';
            $return['data'] = $v;
            return $return;
        }
        return $return;
    }

    /**
     * 获取SkuList
     * @param mixed $bill bill
     * @return mixed 返回结果
     */

    public function getSkuList($bill) {
        $model = '';
        $matchResult = $this->confirmItemModel($bill, $model, '2');
        if($matchResult['rsp'] != 'succ') {
            $matchResult = $this->confirmItem($bill);
        }
        $mdlBill = app::get('financebase')->model('bill');
        if($matchResult['rsp'] != 'succ') {
            if($bill['confirm_status'] == '1') {
                $mdlBill->update(array('confirm_status'=>'0', 'confirm_fail_msg'=>$matchResult['msg']), array('id'=>$bill['id']));
            }
            return array(array(), '未匹配到菜鸟账单');
        }
        if($bill['confirm_status'] == '0') {
            $mdlBill->update(array('confirm_status'=>'1', 'confirm_fail_msg'=>''), array('id'=>$bill['id']));
        }
        if($matchResult['type'] == 'order') {
            return $this->getSkuListFromOrderBn($bill, $matchResult['data']);
        }
        if($matchResult['type'] == 'sku') {
            return $this->getSkuListFromSku($bill, $matchResult['data']);
        }
        return $this->getSkuListFromSale($bill, $matchResult['data']);
    }

    protected function getSkuListFromOrderBn($bill, $data) {
        $orderBn = $data['transaction_sn'];
        if(!$orderBn) {
            return array(array(), '订单号未找到');
        }
        $appName = 'ome';
        $order = app::get('ome')->model('orders')->db_dump(array('order_bn'=>$orderBn,'shop_id'=>$bill['shop_id']), 'order_id');
        if(empty($order)) {
            $appName = 'archive';
            $order = app::get('archive')->model('orders')->db_dump(array('order_bn'=>$orderBn,'shop_id'=>$bill['shop_id']), 'order_id');
        }
        if(!$order) {
            return array(array(), '对应的订单系统中不存在');
        }
        $skuList = array(
            'bill' => $bill,
            'ids' => $data['ids'],
            'parent_id' => $data['id'],
            'parent_type' => 'bill_import_order',
            'sku' => array()
        );
        $items = app::get($appName)->model('order_items')->getList('product_id,nums,divide_order_fee',array('order_id'=>$order['order_id'], 'delete'=>'false'));
        foreach ($items as $v) {
            if($skuList['sku'][$v['product_id']]) {
                $skuList['sku'][$v['product_id']]['nums'] += $v['nums'];
                $skuList['sku'][$v['product_id']]['divide_order_fee'] += $v['divide_order_fee'];
            } else {
                $v['bm_id'] = $v['product_id'];
                $skuList['sku'][$v['product_id']] = $v;
            }
        }
        return array($skuList, '获取拆分的明细成功');
    }

    protected function getSkuListFromSku($bill, $data) {
        $model = app::get('financebase')->model('bill_import_sku');
        $rows = $model->getList('bm_id,sku,actual_operation_num', array('summary_id'=>$data['summary_id']));
        $skuList = array(
            'bill' => $bill,
            'ids' => $data['ids'],
            'parent_id' => $data['id'],
            'parent_type' => 'bill_import_sku',
            'sku' => array()
        );
        foreach ($rows as $v) {
            if($skuList['sku'][$v['bm_id']]) {
                $skuList['sku'][$v['bm_id']]['nums'] += $v['actual_operation_num'];
            } else {
                $skuList['sku'][$v['bm_id']] = array(
                    'bm_id' => $v['bm_id'],
                    'nums' => $v['actual_operation_num'],
                    'divide_order_fee' => 0
                );
            }
        }
        return array($skuList, '获取拆分的明细成功');
    }

    protected function getSkuListFromSale($bill, $data) {
        $importId = app::get('financebase')->model('bill_import_summary')->db_dump(array('id'=>$data['summary_id']), 'import_id');
        $importRow = app::get('financebase')->model('bill_import')->db_dump(array('id'=>$importId['import_id']), 'start_time,end_time');
        $startTime = $importRow['start_time'] ? : strtotime(date('Y-m-1'));
        $endTime = $importRow['end_time'] ? : strtotime(date('Y-m-d 23:59:59'));
        $saleFilter = array(
            'sale_time|bthan' => $startTime,
            'sale_time|sthan' => $endTime,
            'shop_id' => $bill['shop_id']
        );
        $sales = app::get('ome')->model('sales')->getList('sale_id', $saleFilter);
        if(empty($sales)) {
            return array(array(), '没有销售单');
        }
        $sql = "select product_id, sum(nums) as totalnum, sum(sales_amount) as sales_all from sdb_ome_sales_items
                    where sale_id in('".implode("','", array_map('current', $sales))."')
                    group by product_id";
        $saleItems = kernel::database()->select($sql);
        $skuList = array(
            'bill' => $bill,
            'ids' => $data['ids'],
            'parent_id' => $data['id'],
            'parent_type' => 'bill_import_sale',
            'sku' => array()
        );
        foreach ($saleItems as $v) {
            $skuList['sku'][$v['product_id']] = array(
                'bm_id' => $v['product_id'],
                'nums' => $v['totalnum'],
                'divide_order_fee' => $v['sales_all']
            );
        }
        return array($skuList, '获取拆分的明细成功');
    }
}