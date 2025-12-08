<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_mdl_branch extends dbeav_model{

    /**
     * 搜索Options
     * @return mixed 返回值
     */
    public function searchOptions(){

    }

    /**
     * _currFilter
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function _currFilter(&$filter){
        $filter['b_type'] = 2;
    }

    public function count($filter=array()){
        $this->_currFilter($filter);
        $row = kernel::single('ome_interface_branch')->count($filter);
        return intval($row[0]['_count']);
    }

    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
        $this->_currFilter($filter);
        $rows = kernel::single('ome_interface_branch')->getList($cols, $filter, $offset, $limit, $orderType);
        return $rows;
    }

    public function delete($filter=array(),$subSdf = 'delete'){
        $this->_currFilter($filter);
        $rows = kernel::single('ome_interface_branch')->delete($filter);
        return $rows;
    }

    function modifier_b_status($row){
        if($row == 1){
            return '是';
        }else{
            return '否';
        }
    }

    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema(){
        $schema = array (
            'columns' => array (
                'branch_id' =>
                array (
                  'type' => 'number',
                  'required' => true,
                  'pkey' => true,
                  'extra' => 'auto_increment',
                  'editable' => false,
                ),
                'branch_bn' =>
                array (
                  'type' => 'varchar(32)',
                  'required' => true,
                  'in_list' => true,
                  'default_in_list' => true,
                  'label' => '仓库编号',
                ),
                'name' =>
                array (
                  'type' => 'varchar(200)',
                  'required' => true,
                  'editable' => false,
                  'is_title' => true,
                  'searchtype' => 'has',
                  'filtertype' => 'normal',
                  'filterdefault' => true,
                  'in_list' => true,
                  'default_in_list' => true,
                  'width' => 130,
                  'label' => '仓库名',
                ),
                'b_status' =>
                array (
                  'type' => 'tinyint(1)',
                  'editable' => false,
                  'label' => '启用',
                  'default' => 1
                ),
            ),
            'idColumn' => 'branch_id',
            'in_list' => array (
                0 => 'branch_bn',
                1 => 'name',
                2 => 'b_status',
            ),
            'default_in_list' => array (
                0 => 'branch_bn',
                1 => 'name',
                2 => 'b_status',
            ),
        );
        return $schema;
    }
}