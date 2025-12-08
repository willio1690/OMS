<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omevirtualwms_mdl_allocate extends dbeav_model{
    public $queue = [];

    /**
     * count
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function count($filter=null){
        $branch_ids = $this->getBranchidByselfwms();
        $sqlstr = '';
        if ($branch_ids)
         {
            $sqlstr.=" AND branch_id not in (".implode(',',$branch_ids).")";
         }
         
         $sql= "select count(*) as _count from sdb_taoguaniostockorder_iso where iso_status not in('3','4') and check_status='2' and ".$this->_filter($filter).'  and create_time >='.(time()-(WAIT_TIME*60)).')'.$sqlstr;
       
        
        
        $row = $this->db->select($sql);
        return intval($row[0]['_count']);
    }

    public function getlist($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
        $branch_ids = $this->getBranchidByselfwms();
        $sqlstr = '';
        if ($branch_ids)
         {
            $sqlstr.=" AND branch_id not in (".implode(',',$branch_ids).")";
         }
        $sql= "select * from sdb_taoguaniostockorder_iso where iso_status not in('3','4') and check_status='2' and ".$this->_filter($filter).$sqlstr.' sand create_time >='.(time()-(WAIT_TIME*60)).') order by create_time desc';
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
                'iso_bn' => array (
                    'type' => 'varchar(32)',
                    'required' => true,
                    'label' => '调拨出入库单号',
                    'editable' => false,
                    'width' =>180,
                    'searchtype' => 'has',
                    'filtertype' => 'yes',
                    'filterdefault' => true,
                    'is_title' => true,
                ),
                'operator' => array (
                    'type' => 'varchar(100)',
                    'label' => '操作人',
                    'editable' => false,
                    'width' =>100,
                ),
               'create_time' => array (
                    'type' => 'time',
                    'label' => '时间',
                    'width' =>160,
                    'editable' => false,
                ),
         
            ),
            'idColumn' => 'iso_bn',
            'in_list' => array (
                0 => 'iso_bn',
                1 => 'operator',
                2 => 'create_time',
               ),
            'default_in_list' => array (
                0 => 'iso_bn',
                1 => 'operator',
                2 => 'create_time',
            ),
        );
        return $schema;
    }

    function getBranchidByselfwms(){
        $oBranch = &app::get('ome')->model('branch');
        $filter['wms_id'] =kernel::single('wms_branch')->getBranchByselfwms();
        
        $branch_list = $oBranch->getList('branch_id', $filter, 0, -1);
        if ($branch_list)
        $branch_ids = array();
        foreach ($branch_list as $branch_list) {
            $branch_ids[] = $branch_list['branch_id'];

        }
        return $branch_ids;
    }
}
