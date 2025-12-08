<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class financebase_mdl_bill extends dbeav_model{

var $shop_name = array();


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
     * modifier_status
     * @param mixed $val val
     * @return mixed 返回值
     */
    public function modifier_status($val)
    {
        $ref = array(0=>'未匹配订单号',1=>'等待核销',2=>'已核销');
        return isset($ref[$val]) ? $ref[$val] : '未匹配订单号';
    }

    /**
     * modifier_fee_type
     * @param mixed $val val
     * @return mixed 返回值
     */
    public function modifier_fee_type($val)
    {
        $ref = array(0=>'收入',1=>'支出');
        return isset($ref[$val]) ? $ref[$val] : '收入';
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

        if(isset($filter['split_time_from'])) { 
            if($filter['split_time_from']){
                $baseWhere[] = ' `split_time` >='.strtotime($filter['split_time_from']);
            }
            unset($filter['split_time_from']);
        }
        
        if(isset($filter['split_time_to'])) { 
            if($filter['split_time_to']){
                $baseWhere[] = ' `split_time` <'.(strtotime($filter['split_time_to'])+86400);
            }
            unset($filter['split_time_to']);
        }
        
        if(isset($filter['shop_id']) && $filter['shop_id']){
            $filter['shop_id'] = array_filter((array)$filter['shop_id']);
            $where .= ' AND `shop_id` in (\''.implode("','", $filter['shop_id'])."') ";
            
        }
        unset($filter['shop_id']);

        if(isset($filter['fee_type']) && 'all' === $filter['fee_type']){
            unset($filter['fee_type']);
        }

        if(isset($filter['status']) && 'all' === $filter['status']){
            unset($filter['status']);
        }
        return parent::_filter($filter, $tableAlias, $baseWhere).$where;
    }

    /**
     * 获取OneByBase
     * @param mixed $shop_id ID
     * @param mixed $unique_id ID
     * @return mixed 返回结果
     */
    public function getOneByBase($shop_id,$unique_id)
    {
        $sql = "select content from ".kernel::database()->prefix."financebase_bill_base where shop_id = '".$shop_id."' and unique_id = ".$unique_id." ";
        $row = $this->db->selectrow($sql);
        return $row ? json_decode($row['content'],1) : array();
    }

    /**
     * 获取BillCategorySplitCount
     * @param mixed $filter filter
     * @return mixed 返回结果
     */
    public function getBillCategorySplitCount($filter) {
        $undefined = app::get('financebase')->getConf('expenses.rule.undefined');
        if(isset($filter['bill_category'])) {
            if(!$filter['bill_category']) {
                unset($filter['bill_category']);
            } elseif($filter['bill_category'] == $undefined['bill_category']) {
                $filter['bill_category'] = "";
            }
        }
        $sql = 'select bill_category,sum(money) total_money,
                    sum(case when split_status in("2","3") then money end) split_money,
                    sum(case when split_status not in("2","3") then money end) unsplit_money
                from sdb_financebase_bill
                where '.$this->_filter($filter).'
                group by bill_category';
        $list = $this->db->select($sql);
        foreach ($list as $k => $v) {
            $list[$k]['bill_category'] = $v['bill_category'] ? : $undefined['bill_category'];
            $list[$k]['total_money'] = $v['total_money'] ? : 0;
            $list[$k]['split_money'] = $v['split_money'] ? : 0;
            $list[$k]['unsplit_money'] = $v['unsplit_money'] ? : 0;
        }
        return $list;
    }
}
