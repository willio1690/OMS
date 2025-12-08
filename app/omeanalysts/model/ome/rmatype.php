<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeanalysts_mdl_ome_rmatype extends dbeav_model{
	
    /**
     * 获取_rmatype
     * @param mixed $filter filter
     * @return mixed 返回结果
     */
    public function get_rmatype($filter=null){
        //销售额
        //$sql = 'SELECT sum(r.num) as num , p.name as name FROM sdb_omeanalysts_ome_rmatype as r , sdb_ome_return_product_problem as p WHERE r.problem_id = p.problem_id and '.$this->_filter($filter) .' GROUP BY r.problem_id,p.name';

		$sql = 'SELECT sum(r.num) as num , p.problem_name as name FROM sdb_omeanalysts_ome_rmatype as r right join sdb_ome_return_product_problem as p on r.problem_id = p.problem_id and '.$this->_filter($filter) .' GROUP BY r.problem_id,p.problem_name ORDER BY p.problem_name';

        $row = $this->db->select($sql);
        return $row;
    }

	
	
    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter,$tableAlias=null,$baseWhere=null){
        $where = array(1);
        if(isset($filter['time_from']) && $filter['time_from']){
            $where[] = ' r.createtime >='.strtotime($filter['time_from']);
        }
        if(isset($filter['time_to']) && $filter['time_to']){
            $where[] = ' r.createtime <'.(strtotime($filter['time_to'])+86400);
        }
        if(isset($filter['problem_id']) && $filter['problem_id']){
            $where[] = ' r.bn LIKE \''.addslashes($filter['problem_id']).'%\'';
        }
		if(isset($filter['type_id']) && $filter['type_id']){
            $where[] = ' r.shop_id LIKE \''.addslashes($filter['type_id']).'%\'';
        }

        return parent::_filter($filter,'r',$baseWhere)." AND ".implode(' AND ', $where);
    }

}