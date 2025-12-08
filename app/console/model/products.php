<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_mdl_products extends material_mdl_basic_material{

    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */
    public function table_name($real = false)
    {
        $table_name = 'sdb_material_basic_material';
        
        return $table_name;
	}

    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema(){
        return app::get('material')->model('basic_material')->get_schema();
    }

    function countAnother($filter=null){
        $other_table_name = app::get('ome')->model('branch_product')->table_name(1);
        $count = ' COUNT(*) ';
        if (isset($filter['product_group'])){
            $count = ' COUNT( DISTINCT '.$this->table_name(1).'.product_id ) ';
        }
        $strWhere = '';
        if(isset($filter['branch_id'])){
            if (is_array($filter['branch_id'])){
                $strWhere = ' AND '.$other_table_name.'.branch_id IN ('.implode(',', $filter['branch_id']).') ';
            }else {
                $strWhere = ' AND '.$other_table_name.'.branch_id = '.$filter['branch_id'];
            }
        }
        $sql = 'SELECT '.$count.'as _count FROM `'.$this->table_name(1).'` LEFT JOIN  '.$other_table_name.'  ON '.$this->table_name(1).'.product_id = '.$other_table_name.'.product_id WHERE '.$this->_filter($filter) . $strWhere;

        $row = $this->db->selectrow($sql);

        return intval($row['_count']);
    }

    function getListAnother($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
        if(!$cols){
            $cols = $this->defaultCols;
        }
        if(!empty($this->appendCols)){
            $cols.=','.$this->appendCols;
        }
        if($this->use_meta){
             $meta_info = $this->prepare_select($cols);
        }

        $other_table_name = app::get('ome')->model('branch_product')->table_name(1);
        $strWhere = '';
        if(isset($filter['branch_id'])){
            if (is_array($filter['branch_id'])){
                $strWhere = ' AND '.$other_table_name.'.branch_id IN ('.implode(',', $filter['branch_id']).') ';
            }else {
                $strWhere = ' AND '.$other_table_name.'.branch_id = '.$filter['branch_id'];
            }
        }
        $strGroup = '';
        if(isset($filter['product_group'])){
            $strGroup = ' GROUP BY '.$this->table_name(1).'.product_id ';
        }

        $tmpCols = array();
        foreach(explode(',',$cols) as $col){
            if(strpos($col, 'as column')){
                $tmpCols[] = $col;
            }else{
                $tmpCols[] = $this->table_name(true).'.'.$col;
            }
        }
        $cols = implode(',',$tmpCols);
        unset($tmpCols);

        $orderType = $orderType?$orderType:$this->defaultOrder;
        $sql = 'SELECT '.$cols.' FROM `'.$this->table_name(true).'` LEFT JOIN  '.$other_table_name.'  ON '.$this->table_name(1).'.product_id = '.$other_table_name.'.product_id WHERE '.$this->_filter($filter) . $strWhere;

        if($strGroup)$sql.=$strGroup;
        if($orderType) {$this->table_name(true).'.'.
            $sql.=' ORDER BY ';
            if (is_array($orderType)){
                $sql .= $this->table_name(true).'.';
                $sql .= implode(','.$this->table_name(true).'.' , $orderType);
            }else {
                $sql .= $this->table_name(true).'.'.$orderType;
            }
        }

        $data = $this->db->selectLimit($sql,$limit,$offset);
        $this->tidy_data($data, $cols);
        if($this->use_meta && count($meta_info['metacols']) && $data){
            foreach($meta_info['metacols'] as $col){
                $obj_meta = new dbeav_meta($this->table_name(true),$col,$meta_info['has_pk']);
                $obj_meta->select($data);
            }
        }
        return $data;
    }

}