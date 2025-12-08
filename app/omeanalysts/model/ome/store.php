<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeanalysts_mdl_ome_store extends dbeav_model{
    /**
     * 获取_outstock
     * @param mixed $filter filter
     * @return mixed 返回结果
     */
    public function get_outstock($filter=null) {
		$sql = 'SELECT SUM(number) AS outstock FROM '.
            kernel::database()->prefix.'ome_delivery_items as I LEFT JOIN '.
            kernel::database()->prefix.'ome_delivery as D ON I.delivery_id=D.delivery_id '.
            'WHERE D.process=\'true\' and '.$this->d_filter($filter);
		$row = $this->db->select($sql);
		return intval($row[0]['outstock']);	
	}
	
    /**
     * 获取_store
     * @param mixed $filter filter
     * @return mixed 返回结果
     */
    public function get_store($filter=null) {
		$sql = 'SELECT sum(B.store) as store,sum(B.store_freeze) AS store_freeze,sum(B.arrive_store) as arrive_store FROM '.
            kernel::database()->prefix.'material_basic_material AS P LEFT JOIN '.
            kernel::database()->prefix.'ome_branch_product AS B ON P.bm_id=B.product_id '.
            'WHERE '.$this->_filter($filter);
		$row = $this->db->select($sql);
		return $row[0];
	}
	
    /**
     * 获取_value
     * @param mixed $filter filter
     * @return mixed 返回结果
     */
    public function get_value($filter=null) {
        if($filter['type_id']){
            $baseWhere = ' and branch_id='.$filter['type_id'];
        }
		$sql = 'SELECT I.product_id,sum(I.num) as num,sum(I.num*I.price) as price FROM '.
            kernel::database()->prefix.'purchase_po AS O LEFT JOIN '.
            kernel::database()->prefix.'purchase_po_items AS I ON O.po_id=I.po_id '.
            'WHERE 1 '.$baseWhere.' group by I.product_id';
        $rows = $this->db->select($sql);
        $store_value = 0;
        foreach($rows as $row){
            $sql = "SELECT sum(store) as store FROM sdb_ome_branch_product WHERE product_id=".intval($row['product_id']).
                $baseWhere.' group by product_id';
            $product = $this->db->select($sql);
            $store_value += $row['num']?number_format(($product[0]['store']*($row['price']/$row['num'])),2,",",""):0;
        }
        return $store_value;
	}
    
    /**
     * count
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function count($filter=null) {
		$sql = 'SELECT count(*) AS _count FROM '.
            kernel::database()->prefix.'sdb_material_basic_material AS P LEFT JOIN '.
            kernel::database()->prefix.'ome_branch_product AS B ON P.bm_id=B.product_id '.
            'WHERE '.$this->_filter($filter);
		$row = $this->db->select($sql);
		return intval($row[0]['_count']);
		}
	
	public function getList($cols='*', $filter=array(), $offset=0, $limit=-1,$orderType=null) {
        $sql = 'SELECT P.bm_id,P.material_bn AS bn,P.material_name AS name,sum(B.store) as store,sum(B.store_freeze) as store_freeze,1 as arrive_store FROM '.
            kernel::database()->prefix.'material_basic_material AS P LEFT JOIN '.
            kernel::database()->prefix.'ome_branch_product AS B ON P.bm_id=B.product_id '.
            'WHERE '.$this->_filter($filter).' group by bm_id';
        if($orderType)$sql.=' ORDER BY '.(is_array($orderType)?implode($orderType,' '):$orderType);
        
        $rows = $this->db->selectLimit($sql,$limit,$offset);
        $this->tidy_data($rows,$cols);
        if($filter['type_id']){
            $baseWhere = ' and branch_id='.$filter['type_id'];
        }
        foreach($rows as $key=>$val){
            $sql = 'SELECT sum(num) as num,sum(num*price) as price FROM '.
                kernel::database()->prefix.'purchase_po AS O LEFT JOIN '.
                kernel::database()->prefix.'purchase_po_items AS I ON O.po_id=I.po_id '.
                'where product_id='.$val['product_id'].$baseWhere.' group by product_id';
            $row = $this->db->select($sql);
            $rows[$key]['value'] = isset($row[0]['num'])?$val['store']*($row[0]['price']/$row[0]['num']):0;

            $sql2 = 'SELECT SUM(arrive_store) AS total FROM sdb_ome_branch_product WHERE product_id ='.$val['product_id'];
            $count = $this->db->selectrow($sql2);
            $rows[$key]['arrive_store'] = $count['total'];
        }
        return $rows;
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
    	if (isset($filter['type_id']) && $filter['type_id']){
    		$where[] = " B.branch_id=".$filter['type_id'];
    	}
    	return implode(' AND ', $where);
	}
	
    /**
     * d_filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function d_filter($filter,$tableAlias=null,$baseWhere=null) {
		$where = array(1);
    	if (isset($filter['time_from']) && $filter['time_from']) {
    		$where[] = " D.delivery_time >=".strtotime($filter['time_from']);
    	}
    	if (isset($filter['time_to']) && $filter['time_to']) {
    		$where[] = " D.delivery_time <".(strtotime($filter['time_to'])+86400);
    	}
    	if (isset($filter['type_id']) && $filter['type_id']){
            $where[] = ' D.branch_id =\''.$filter['type_id'].'\'';
    	}	
    	
    	return implode(' and ', $where);			
    }
	
    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema(){
        $schema = array (
            'columns' => array(
                'bn' => array (
                    'type' => 'varchar(30)',
                    'label' => '基础物料编码',
                    'width' => 85,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'editable' => false,
                ),
                'name' => array (
                    'type' => 'varchar(200)',
                    'required' => true,
                    'default' => '',
                    'label' => '基础物料名称',
                    'width' => 160,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'editable' => false,
                ), 
                'brand_name' => array (
                    'type' => 'table:brand@ome',
                    'label' => '品牌',
                    'width' => 100,
                    'is_title' => true,
                    'required' => true,
                    'comment' => '品牌名称',
                    'editable' => false,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                ),
                'store' => array (
                    'type' => 'number',
                    'editable' => false,
                    'comment' => '库存（各仓库 的库存总和）',
                    'label' => '库存量',
                    'default' => 0,
                    'width' => 65,
                    'label' => '库存',
                ),
                'store_freeze' => array (
                    'type' => 'number',
                    'label' => '预占库存量',
                    'width' => 90,
                    'editable' => false,
                    'default' => 0,
                ),
                'arrive_store' => array (
                    'type' => 'number',
                    'label' => '在途库存量',
                    'width' => 90,		
                    'editable' => false,
                    'default' => 0,
                ),
                'value' => array(
                    'type' => 'money',
                    'label' => '库存价值',
                    'width' => 90,
                    'editable' => false,
                    'orderby' => false,
                ),
            ),
            'idColumn' => 'bn',
            'in_list' => array(
                0 => 'bn',
                1 => 'name',
                2 => 'brand_name',
                3 => 'store',
                4 => 'store_freeze',
                5 => 'arrive_store',
                6 => 'value',
            ),
            'default_in_list' => array(
                0 => 'bn',
                1 => 'name',
                2 => 'brand_name',
                3 => 'store',
                4 => 'store_freeze',
                5 => 'arrive_store',
                6 => 'value',
            ),
        );
        return $schema;
    }
	
}