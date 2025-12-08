<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 京准通账单model类
 *
 * @author wangbiao<wangbiao@shopex.cn>
 * @version $Id: Z
 */
class financebase_mdl_jingzhuntong extends dbeav_model
{
    public $has_export_cnf = true;
    public $filter_use_like = true;
    public $export_name = '京准通账单明细';
    
    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */

    public function table_name($real=false)
    {
        $tableName = 'bill';
        
        return $real ? kernel::database()->prefix.'financebase_'.$tableName : $tableName;
    }
    
    /**
     * 统计数据
     * 
     * @param unknown $filter
     * @return unknown
     */
    public function getTotal($filter)
    {
        $sql = 'select bill_category, sum(money) total_money,
                    sum(case when confirm_status="1" then money end) confirm_money,
                    sum(case when confirm_status="0" then money end) unconfirm_money
                from sdb_financebase_bill
                where '. $this->_filter($filter);
        $row = $this->db->select($sql);
        return $row;
    }
}
