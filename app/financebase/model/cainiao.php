<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class  financebase_mdl_cainiao extends dbeav_model{
    public $has_export_cnf = true;
    public $filter_use_like = true;
    public $export_name = '支付宝明细';
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
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter, $tableAlias = NULL, $baseWhere = NULL){
        if(isset($filter['bill_category'])) {
            if($filter['bill_category'] == 'all') {
                unset($filter['bill_category']);
            }
            $undefined = app::get('financebase')->getConf('expenses.rule.undefined');
            if ($filter['bill_category'] == $undefined['bill_category']) {
                $filter['bill_category'] = "";
            }
        }
        if(isset($filter['confirm_status'])) {
            if($filter['confirm_status'] == '-1') {
                unset($filter['confirm_status']);
            }
        }
        $billCategory = array('-1');
        foreach (app::get('financebase')->model('expenses_rule')->getCainiaoBillCategory() as $v) {
            $billCategory[] = $v['bill_category'];
        }
        $where = ' AND `bill_category` in("' . implode('","', $billCategory) . '")';
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


        if(isset($filter['search_value']) and $filter['search_value'])
        {
            $filter[$filter['search_key']] = $filter['search_value'];
            unset($filter['search_key'],$filter['search_value']);
        }


        return parent::_filter($filter, $tableAlias, $baseWhere).$where;
    }

    /**
     * 获取Total
     * @param mixed $filter filter
     * @return mixed 返回结果
     */
    public function getTotal($filter) {
        $sql = 'select bill_category, sum(money) total_money,
                    sum(case when confirm_status="1" then money end) confirm_money,
                    sum(case when confirm_status="0" then money end) unconfirm_money
                from sdb_financebase_bill
                where '.$this->_filter($filter).'
                group by bill_category';
        $row = $this->db->select($sql);
        return $row;
    }

}
