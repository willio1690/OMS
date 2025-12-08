<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class  financebase_mdl_base extends dbeav_model{


    var $has_export_cnf = true;
    var $defaultOrder = array('id DESC');
    public $filter_use_like = true;
    public $export_name = '店铺收支明细';




    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */
    public function table_name($real=false){
        $tableName = 'bill';
        return $real ? kernel::database()->prefix.'financebase_'.$tableName : $tableName;

    }

    /**
     * 搜索Options
     * @return mixed 返回值
     */
    public function searchOptions(){
        return array();
    }

    /**
     * modifier_shop_id
     * @param mixed $val val
     * @return mixed 返回值
     */
    public function modifier_shop_id($val)
    {
        if(!isset($this->shop_name[$val])){
            $row = app::get('ome')->model('shop')->getList('name',array('shop_id'=>$val),0,1);
            if($row){
                $this->shop_name[$val] = $row[0]['name'];
            }else{
                return '';
            }
            
        }
        return $this->shop_name[$val];
    }

    /**
     * modifier_bill_category
     * @param mixed $col col
     * @return mixed 返回值
     */
    public function modifier_bill_category($col) {
        return $col ? : '未识别类型';
    }

    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter, $tableAlias = NULL, $baseWhere = NULL){
        if(isset($filter['time_from']) && $filter['time_from']){
            $where .= ' AND `trade_time` >='.strtotime($filter['time_from']);
        }
        unset($filter['time_from']);
        
        if(isset($filter['time_to']) && $filter['time_to']){
            $where .= ' AND `trade_time` <'.(strtotime($filter['time_to'])+86400);
        }
        unset($filter['time_to']);

        if(isset($filter['shop_id']) && $filter['shop_id'] = array_filter((array)$filter['shop_id'])){
            $where .= ' AND `shop_id` in (\''.implode("','", $filter['shop_id'])."') ";
        }
        unset($filter['shop_id']);

        if(isset($filter['bill_status']))
        {
            $filter['bill_status'] == 'succ' and $filter['disabled'] = 'false';
            $filter['bill_status'] == 'fail' and $filter['disabled'] = 'true';
            unset($filter['bill_status']);
        }
        if(isset($filter['bill_category'])) {
            if($filter['bill_category'] == 'all') {
                unset($filter['bill_category']);
            }
            $undefined = app::get('financebase')->getConf('expenses.rule.undefined');
            if ($filter['bill_category'] == $undefined['bill_category']) {
                $filter['bill_category'] = "";
            }
        }

        if(isset($filter['search_value']) and $filter['search_value'])
        {
            $filter[$filter['search_key']] = $filter['search_value'];
            unset($filter['search_key'],$filter['search_value']);
        }

        if($filter['money_type']) {
            if($filter['money_type'] == 'positive') {
                $filter['money|than'] = 0;
            }
            if($filter['money_type'] == 'negative') {
                $filter['money|lthan'] = 0;
            }
            unset($filter['money_type']);
        }
    
        if(isset($filter['trade_type']) and $filter['trade_type'])
        {
            $where .= " AND `trade_type` = '".$filter['trade_type']."'";
        }
        unset($filter['trade_type']);
    
        return parent::_filter($filter, $tableAlias, $baseWhere).$where;
    }

    /**
     * 获取Total
     * @param mixed $filter filter
     * @return mixed 返回结果
     */
    public function getTotal($filter) {
        $sql = 'select sum(case when money>0 then money end) total_positive,
                    sum(case when money<0 then money end) total_negative
                from sdb_financebase_bill
                where '.$this->_filter($filter);
        return $this->db->selectrow($sql);
    }

}
