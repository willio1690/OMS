<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/11/26 10:49:07
 * @describe: model层
 * ============================
 */
class financebase_mdl_expenses_show extends dbeav_model {

    /**
     * 获取_schema
     * @return mixed 返回结果
     */

    public function get_schema(){
        $schema = array (
            'columns' => array (
                'bill_category' =>
                array (
                  'type'  => 'varchar(32)',
                  'label' => '具体类别',
                  'width' => 120,
                  'order' => 1,
                ),
                'total_money' =>
                array (
                  'type'  => 'money',
                  'label' => '总费用',
                  'width' => 120,
                  'order' => 2,
                ),
                'confirm_money' =>
                array (
                  'type'  => 'money',
                  'label' => '已对账金额',
                  'width' => 120,
                  'order' => 3,
                ),
                'unconfirm_money' =>
                array (
                  'type'  => 'money',
                  'label' => '未对账金额',
                  'width' => 120,
                  'order' => 4,
                ),
                'split_money' =>
                array (
                  'type'  => 'money',
                  'label' => '已拆分金额',
                  'width' => 120,
                  'order' => 5,
                ),
                'unsplit_money' =>
                array (
                  'type'  => 'money',
                  'label' => '未拆分金额',
                  'width' => 120,
                  'order' => 6,
                ),
                'split_rule' =>
                array (
                  'type'  => 'varchar(32)',
                  'label' => '拆分规则',
                  'width' => 120,
                  'order' => 7,
                ),
            ),
            'idColumn' => 'bill_category',
            'in_list' => array(
                'bill_category',
                'total_money',
                'confirm_money',
                'unconfirm_money',
                'split_money',
                'unsplit_money',
                'split_rule',
            ),
            'default_in_list' => array(
                'bill_category',
                'total_money',
                'confirm_money',
                'unconfirm_money',
                'split_money',
                'unsplit_money',
                'split_rule',
            ),
        );
        return $schema;
    }

    private function getBillCategory($billCategoryFilter, $offset=0, $limit=-1) {
        $undefined = app::get('financebase')->getConf('expenses.rule.undefined');
        $billCategory = array();
        if($billCategoryFilter) {
            if($billCategoryFilter == $undefined['bill_category']) {
                $billCategory[] = "";
            } else {
                $billCategory[] = $billCategoryFilter;
            }
        } else {
            $allBC = app::get('financebase')->model('expenses_rule')->getBillCategory();
            if($limit == '-1') {
                $allBC = array($allBC);
            } else {
                $allBC = array_chunk($allBC, $limit);
            }
            $allBC = $allBC[$offset/$limit];
            foreach ($allBC as $v) {
                $billCategory[] = $undefined['bill_category'] == $v['bill_category'] ? "" : $v['bill_category'];
            }
        }
        return $billCategory;
    }

    /**
     * count
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function count($filter=null){
        if(!$filter['operation']) {
            return 0;
        }
        return count($this->getBillCategory($filter['bill_category']));
    }

    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null) {
        if(!$filter['operation']) {
            return array();
        }
        $billCategory = $this->getBillCategory($filter['bill_category'], $offset, $limit);
        if(isset($filter['bill_category'])) {
            unset($filter['bill_category']);
        }
        $undefined = app::get('financebase')->getConf('expenses.rule.undefined');
        $allBC = app::get('financebase')->model('expenses_rule')->getBillCategory();
        $billCategoryRule = array();
        foreach ($allBC as $v) {
            $billCategoryRule[$v['bill_category']] = $v['split_rule'];
        }
        if(empty($billCategory)) {
            return array();
        }
        $cainiao = app::get('financebase')->model('expenses_rule')->getCainiaoBillCategory();
        $cainiaoCategory = array();
        foreach ($cainiao as $v) {
            $cainiaoCategory[] = $v['bill_category'];
        }
        $sql = 'select bill_category,sum(money) total_money,
                    sum(case when confirm_status="1" and bill_category in("'.implode('","', $cainiaoCategory).'") then money end) confirm_money,
                    sum(case when confirm_status="0" and bill_category in("'.implode('","', $cainiaoCategory).'") then money end) unconfirm_money,
                    sum(case when split_status in("2","3") then money end) split_money,
                    sum(case when split_status not in("2","3") then money end) unsplit_money
                from sdb_financebase_bill
                where '.(count($billCategory) == 1 ? 'bill_category in ("'.implode('","', $billCategory).'")' : '1').'
                    and '.app::get('financebase')->model('bill')->_filter($filter).'
                group by bill_category';
        $list = $this->db->select($sql);
        $bcrn = app::get('financebase')->model('expenses_rule')->getSplitInfo();
        $data = array();
        foreach ($list as $k => $v) {
            $index = array_search($v['bill_category'], $billCategory);
            if($index !== false) {
                unset($billCategory[$index]);
            }
            $data[] = array(
                'bill_category' => $v['bill_category'] ? : $undefined['bill_category'],
                'total_money' => $v['total_money'] ? : 0,
                'confirm_money' => $v['confirm_money'] ? : 0,
                'unconfirm_money' => $v['unconfirm_money'] ? : 0,
                'split_money' => $v['split_money'] ? : 0,
                'unsplit_money' => $v['unsplit_money'] ? : 0,
                'split_rule' => $bcrn['split_rule'][$billCategoryRule[($v['bill_category'] ? : $undefined['bill_category'])]],
            );
        }
        foreach ($billCategory as $v) {
            $v = $v ? : $undefined['bill_category'];
            $data[] = array(
                'bill_category' => $v,
                'total_money' => 0,
                'confirm_money' => 0,
                'unconfirm_money' => 0,
                'split_money' => 0,
                'unsplit_money' => 0,
                'split_rule' => $bcrn['split_rule'][$billCategoryRule[$v]],
            );
        }
        return $data;
    }

    /**
     * 获取IndexTotal
     * @param mixed $filter filter
     * @return mixed 返回结果
     */
    public function getIndexTotal($filter) {
        $filter['bill_category'] = $this->getBillCategory($filter['bill_category']);
        if(count($filter['bill_category']) > 1 ) {
            unset($filter['bill_category']);
        }
        $cainiao = app::get('financebase')->model('expenses_rule')->getCainiaoBillCategory();
        $cainiaoCategory = array();
        foreach ($cainiao as $v) {
            $cainiaoCategory[] = $v['bill_category'];
        }
        $sql = 'select sum(money) total_money,
                    sum(case when confirm_status="1" and bill_category in("'.implode('","', $cainiaoCategory).'") then money end) confirm_money,
                    sum(case when confirm_status="0" and bill_category in("'.implode('","', $cainiaoCategory).'") then money end) unconfirm_money,
                    sum(case when split_status in("2","3") then money end) split_money,
                    sum(case when split_status not in("2","3") then money end) unsplit_money
                from sdb_financebase_bill
                where '.app::get('financebase')->model('bill')->_filter($filter);
        $row = $filter['operation'] ? $this->db->selectrow($sql) : array();
        $detail = array(
            '总费用' => array(
                'name' => '总费用',
                'memo' => '总费用',
                'icon' => 'money.gif',
                'value' => $row['total_money'] ? : 0,
            ),
            '已对账费用' => array(
                'name' => '已对账费用',
                'memo' => '和菜鸟已对账费用',
                'icon' => 'money.gif',
                'value' => $row['confirm_money'] ? : 0,
            ),
            '未对账费用' => array(
                'name' => '未对账费用',
                'memo' => '和菜鸟未对账费用',
                'icon' => 'money.gif',
                'value' => $row['unconfirm_money'] ? : 0,
            ),
            '已拆分费用' => array(
                'name' => '已拆分费用',
                'memo' => '已拆分费用',
                'icon' => 'money.gif',
                'value' => $row['split_money'] ? : 0,
            ),
            '未拆分费用' => array(
                'name' => '未拆分费用',
                'memo' => '未拆分费用',
                'icon' => 'money.gif',
                'value' => $row['unsplit_money'] ? : 0,
            ),
        );
        return $detail;
    }

    /**
     * exportName
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function exportName($data) {
        return '费用对账拆分汇总' . date('Y-m-d');
    }
}