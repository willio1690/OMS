<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omevirtualwms_mdl_stockdump extends dbeav_model{
    public $queue = [];

    /**
     * count
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function count($filter=null){
        $branch_ids = $this->app->model('allocate')->getBranchidByselfwms();
        $sqlstr = '';
        if ($branch_ids)
         {
            $sqlstr.=" AND from_branch_id not in (".implode(',',$branch_ids).")";
         }
       $sql = "SELECT count(*) as _count from sdb_console_stockdump where in_status ='0' and self_status='1' and ".$this->_filter($filter).$sqlstr; 
        
        $row = $this->db->select($sql);
        return intval($row[0]['_count']);
    }
    public function getlist($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
        $branch_ids = $this->app->model('allocate')->getBranchidByselfwms();
        $sqlstr = '';
        if ($branch_ids)
         {
            $sqlstr.=" AND from_branch_id not in (".implode(',',$branch_ids).")";
         }
        $sql= "select * from sdb_console_stockdump where in_status ='0' and self_status='1' and ".$this->_filter($filter).$sqlstr; 

        //$rows = $this->db->select($sql);
        $rows = $this->db->selectLimit($sql,$limit,$offset);
           
         return $rows;
    }
    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema(){
        $schema = array (
            'columns' => array (
                'stockdump_bn' => array (
                    'type' => 'varchar(32)',
                    'required' => true,
                    'label' => '转储单号',
                    'comment' => '转储单号',
                    'editable' => false,
                    'width' =>180,
                    'is_title' => true,
                ),
                'operator_name' => array (
                    'type' => 'varchar(100)',
                    'label' => '操作人',
                    'comment' => '操作人',
                    'editable' => false,
                    'width' =>100,
                ),
               'create_time' => array (
                    'type' => 'time',
                    'label' => '时间',
                    'comment' => '单据生成时间',
                    'width' =>160,
                    'editable' => false,
                ),
         
            ),
            'idColumn' => 'stockdump_bn',
            'in_list' => array (
                0 => 'stockdump_bn',
                1 => 'operator_name',
                2 => 'create_time',
               ),
            'default_in_list' => array (
                0 => 'stockdump_bn',
                1 => 'operator_name',
                2 => 'create_time',
            ),
        );
        return $schema;
    }
}
