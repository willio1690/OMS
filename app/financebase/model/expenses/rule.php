<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/11/23 17:21:05
 * @describe: model层
 * ============================
 */
class financebase_mdl_expenses_rule extends dbeav_model {
    private $billCategory;
    private $cainiaoBillCategory;
    private $splitType = array("sku"=>"sku");
    private $splitRule = array(""=>"","unsplit"=>"不拆仅呈现","price"=>"按价值权重","weight"=>"按重量权重","num"=>"按件数权重","volume"=>"按体积权重");

    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */

    public function table_name($real=false)
    {
        $table_name = 'bill_category_rules';
        if($real){
            return DB_PREFIX.'financebase_'.$table_name;
        }else{
            return $table_name;
        }
    }

    /**
     * 获取BillCategory
     * @return mixed 返回结果
     */
    public function getBillCategory() {
        if(!$this->billCategory) {
            $rows = $this->getList('bill_category,split_type,split_rule');
            $undefined = app::get('financebase')->getConf('expenses.rule.undefined');
            $this->billCategory = array($undefined);
            $this->billCategory = array_merge($this->billCategory, $rows);
        }
        return $this->billCategory;
    }

    /**
     * 获取CainiaoBillCategory
     * @return mixed 返回结果
     */
    public function getCainiaoBillCategory() {
        if(!$this->cainiaoBillCategory) {
            $this->cainiaoBillCategory = $this->getList('bill_category,split_type,split_rule', array('business_type'=>'cainiao'));
        }
        return $this->cainiaoBillCategory;
    }

    /**
     * 获取SplitInfo
     * @return mixed 返回结果
     */
    public function getSplitInfo() {
        return array(
            'split_type' => $this->splitType,
            'split_rule' => $this->splitRule,
        );
    }

    public function getExpensesList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null) {
        $rows = parent::getList($cols, $filter, $offset, $limit, $orderType);
        if($offset != 0) {
            return $rows;
        }
        $first = app::get('financebase')->getConf('expenses.rule.undefined');
        if(!$first) {
            $first = array(
                'rule_id' => 'undefined',
                'bill_category' => '未识别类型',
            );
            app::get('financebase')->setConf('expenses.rule.undefined', $first);
        }
        array_unshift($rows, $first);
        return $rows;
    }

    /**
     * modifier_split_type
     * @param mixed $col col
     * @return mixed 返回值
     */
    public function modifier_split_type($col) {
        return $col ? $this->splitType[$col] : '';
    }

    /**
     * modifier_split_rule
     * @param mixed $col col
     * @return mixed 返回值
     */
    public function modifier_split_rule($col) {
        return $col ? $this->splitRule[$col] : '';
    }
}