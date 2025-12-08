<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class financebase_mdl_bill_unverification extends dbeav_model{

    var $defaultOrder = array('id DESC');
    public $filter_use_like = true;

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
     * 搜索Options
     * @return mixed 返回值
     */
    public function searchOptions(){
        return array();
    }


    /**
     * count
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function count($filter=null){
        $row = $this->db->select('SELECT count(*) as _count FROM (select order_bn from `'.$this->table_name(1).'` WHERE '.$this->_filter($filter) . ' group by order_bn ) as a ');
        return intval($row[0]['_count']);
    }

    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){

        $cols = 'order_bn,shop_id,sum(money) as bill_money,0 as ar_money';

        $sql = 'SELECT '.$cols.' FROM `'.$this->table_name(true).'` WHERE '.$this->_filter($filter) . ' group by order_bn ORDER BY id desc';

        $data = $this->db->selectLimit($sql,$limit,$offset);
        if($data)
        {
            //$sql = "select order_bn,sum(case when money > 0 then money else 0 end) as income_money,sum(case when money < 0 then money else 0 end) as outcome_money FROM `".$this->table_name(true)."` where order_bn in (".implode(',', array_column($data,'order_bn')).") group by order_bn  ";
            $sql = "select order_bn,sum(money) as ar_money from sdb_finance_ar where order_bn in (".implode(',', array_column($data,'order_bn')).") group by order_bn";
            $extra_data = $this->db->select($sql);

            if($extra_data)
            {
                $extra_data = array_column($extra_data,null,'order_bn');

                foreach ($data as $k => $v) {
                    $extra_data[$v['order_bn']] and $data[$k] = array_merge($v,$extra_data[$v['order_bn']]);
                }
            }

        }

        $this->tidy_data($data, $cols);
        return $data;
    }


    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema(){

        $columns = array();

        $columns['order_bn'] = array('type'=>'varchar(32)','label'=>'订单号','comment'=>'订单号','searchtype'=>'nequal','width'=>200,'order'=>10);
        $columns['bill_money'] = array('type'=>'money','label'=>'流水总费用','comment'=>'收入费用','width'=>150,'order'=>20);
        $columns['ar_money'] = array('type'=>'money','label'=>'单据总费用','comment'=>'支出费用','width'=>150,'order'=>30);
        $columns['shop_id'] = array('type'=>'varchar(32)','label'=>'所属店铺','comment'=>'所属店铺','width'=>150,'order'=>40);

        $schema['columns'] = $columns;
        $schema['idColumn'] = 'order_bn';
        $schema['in_list'] = array_keys($columns);
        $schema['default_in_list'] = array_keys($columns);

        return $schema;
    }


    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter, $tableAlias = NULL, $baseWhere = NULL){

        if(isset($filter['status']) ){
            $where .= ' AND `status` =\''.$filter['status']."'";
        }
        unset($filter['status']);

        return parent::_filter($filter, $tableAlias, $baseWhere).$where;
    }




}
