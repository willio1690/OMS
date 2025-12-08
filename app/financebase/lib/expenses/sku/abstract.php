<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/11/25 11:09:47
 * @describe: 类
 * ============================
 */
abstract class financebase_expenses_sku_abstract {

    protected $type; //拆分维度

    protected $rule; //拆分规则

    protected $failMsg = '未获取到价值项'; //失败原因

    protected $originalSkuId; //源skuID

    protected $originalSkuCombinationItems; //礼盒详情

    protected function _dealCombinationItems(&$skuList) {
        $this->originalSkuId = array();
        $this->originalSkuCombinationItems = array();
        foreach ($skuList['sku'] as $v) {
            $this->originalSkuId[] = $v['bm_id'];
        }
        $rows = app::get('material')->model('basic_material_combination_items')
                    ->getList('pbm_id,bm_id,material_num',array('pbm_id'=>$this->originalSkuId));
        if(empty($rows)) {
            return;
        }
        $ciBmids = array();
        foreach ($rows as $v) {
            $ciBmids[$v['bm_id']] = $v['bm_id'];
            $item = array('bm_id'=>$v['bm_id'], 'nums'=>$v['material_num']);
            if($this->originalSkuCombinationItems[$v['pbm_id']]) {
                $this->originalSkuCombinationItems[$v['pbm_id']]['items'][] = $item;
            } else {
                $this->originalSkuCombinationItems[$v['pbm_id']] = array(
                    'items' => [$item],
                    'porth' => 'nums'
                );
            }
        }
        $bmRows = app::get('material')->model('basic_material_ext')
                    ->getList('bm_id, cost', array('bm_id'=>$ciBmids));
        $retailPrice = array();
        foreach ($bmRows as $v) {
            $retailPrice[$v['bm_id']] = $v['cost'];
        }
        #将礼盒sku转换成基础sku
        $scItems = array();
        foreach ($skuList['sku'] as $k => $v) {
            if($this->originalSkuCombinationItems[$v['bm_id']]) {
                unset($skuList['sku'][$k]);
                foreach ($this->originalSkuCombinationItems[$v['bm_id']]['items'] as $ik => $iv) {
                    $this->originalSkuCombinationItems[$v['bm_id']]['part_total'] = $v['divide_order_fee'];
                    if($retailPrice[$iv['bm_id']] > 0) {
                        $this->originalSkuCombinationItems[$v['bm_id']]['porth'] = 'retail_amount';
                        $this->originalSkuCombinationItems[$v['bm_id']]['items'][$ik]['nums'] = $iv['nums'] * $v['nums'];
                        $this->originalSkuCombinationItems[$v['bm_id']]['items'][$ik]['retail_amount'] = $iv['nums'] * $retailPrice[$iv['bm_id']] * $v['nums'];
                    }
                }
            }
        }
        foreach ($this->originalSkuCombinationItems as $v) {
            $options = array (
                'part_total'  => $v['part_total'],
                'part_field'  => 'money',
                'porth_field' => $v['porth'],
            );
            $items = kernel::single('ome_order')->calculate_part_porth($v['items'], $options);
            foreach ($items as $iv) {
                $skuList['sku'][$iv['bm_id']]['bm_id'] = $iv['bm_id'];
                $skuList['sku'][$iv['bm_id']]['nums'] += $iv['nums'];
                $skuList['sku'][$iv['bm_id']]['divide_order_fee'] += $iv['money'];
            }
        }
    }

    abstract protected function _getPorthValue($skuList);

    /**
     * [process description]
     * @param  array $skuList array(
     *         'bill' => $bill,
     *         'ids' => '',
     *         'parent_id' => '',
     *         'parent_type' => '', #bill/bill_import_order
     *         'sku' => array(array(
     *             'bm_id' => '',
     *             'nums' => '',
     *             'divide_order_fee' => '',
     *         ))
     * @return array          [description]
     */

    public function process($skuList, $time = 0) {
        $className = get_class($this);
        $className = explode('_', $className);
        $this->type = $className[2];
        $this->rule = $className[3];
        $this->_dealCombinationItems($skuList);
        $porth = $this->_getPorthValue($skuList);
        if(empty($porth)) {
            return array(false, $this->failMsg);
        }
        $time = $time ? : time();
        $expensesSplit = array();
        $hasPorth = false;
        foreach ($skuList['sku'] as $v) {
            if($porth[$v['bm_id']] > 0) {
                $hasPorth = true;
            }
            $expensesSplit[] = array(
                'bm_id' => $v['bm_id'],
                'bill_id' => $skuList['bill']['id'],
                'parent_id' => $skuList['parent_id'],
                'parent_type' => $skuList['parent_type'],
                'trade_time' => $skuList['bill']['trade_time'],
                'split_time' => $time,
                'money' => 0,
                'bill_category' => $skuList['bill']['bill_category'],
                'split_type' => $this->type,
                'split_rule' => $this->rule,
                'porth' => $porth[$v['bm_id']],
                'shop_id' => $skuList['bill']['shop_id'],
            );
        }
        if(!$hasPorth) {
            return array(false, $this->failMsg);
        }
        if($skuList['ids']) {
            $expensesSplitModel = app::get('financebase')->model('expenses_split');
            $olds = $expensesSplitModel->db_dump(['bill_id'=>$skuList['bill']['id']]);
            try{
                $splitStatus = $olds ? '2' : '1';
                app::get('financebase')->model($skuList['parent_type'])->update(['split_status'=>$splitStatus],['id'=>$skuList['ids']]);
            } catch (Exception $e){}
        }
        $options = array (
            'part_total'  => $skuList['bill']['money'],
            'part_field'  => 'money',
            'porth_field' => 'porth',
        );
        $expensesSplit = kernel::single('ome_order')->calculate_part_porth($expensesSplit, $options, 5);
        $expensesSplitModel = app::get('financebase')->model('expenses_split');
        $sql = ome_func::get_insert_sql($expensesSplitModel, $expensesSplit);
        $expensesSplitModel->db->exec($sql);
        return array(true, '拆分完成');
    }

}