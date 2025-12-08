<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_mdl_branch_product_extend extends dbeav_model
{
     var $export_name = '仓库库存';
     
    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */
    public function table_name($real = false)
     {
        if($real){
           $table_name = 'sdb_ome_branch_product_extend';
        }else{
           $table_name = 'branch_product_extend';
        }
        
        return $table_name;
    }
    
    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema()
    {
        $schema = app::get('ome')->model('branch_product_extend')->get_schema();
        
        return $schema;
    }

    /**
     * 列表统计
     * @param   array $filter
     * @return  int
     * @access  public
     * @author cyyr24@sina.cn
     */
    function countlist($filter=null)
    {
        $strWhere = '';
        
        //仓库号
        if(isset($filter['branch_id']) && $filter['branch_id']){
            if (is_array($filter['branch_id'])){
                $strWhere = ' AND a.branch_id IN ('.implode(',', $filter['branch_id']).') ';
            }else {
                $strWhere = ' AND a.branch_id = '.$filter['branch_id'];
            }
        }
        
        //货号
        if(isset($filter['bn']) && $filter['bn']!=''){
            $filter['bn'] = str_replace(array('"', "'"), '', $filter['bn']);
            $sel_sql = "SELECT bm_id FROM sdb_material_basic_material WHERE material_bn='". $filter['bn'] ."'";
            $materialInfo = $this->db->selectrow($sel_sql);
            if($materialInfo){
                $strWhere .= " AND a.product_id=". $materialInfo['bm_id'] ." ";
            }else{
                $strWhere .= " AND a.product_id=-1 "; //不存在的货号
            }
        }
        
        //真实库存
        if(isset($filter['actual_store']) && $filter['actual_store']!=''){
            $strWhere .= ' AND a.store';
            if($filter['_actual_store_search']=='nequal'){
                $strWhere .= ' =';
            }else if($filter['_actual_store_search']=='than'){
                $strWhere .= ' >';
            }else if($filter['_actual_store_search']=='lthan'){
                $strWhere .= ' <';
            }
            $strWhere .= intval($filter['actual_store']);
        }
        
        //可用库存
        if(isset($filter['enum_store']) && $filter['enum_store']!=''){
            $strWhere .= ' AND a.store_freeze';
            if($filter['_enum_store_search']=='nequal'){
                $strWhere .= ' =';
            }else if($filter['_enum_store_search']=='than'){
                $strWhere .= ' >';
            }else if($filter['_enum_store_search']=='lthan'){
                $strWhere .= ' <';
            }
            $strWhere .= intval($filter['enum_store']);
        }
        
        //sdb_ome_branch_product没有唯一主键,通过branch_id+'_'+a.product_id拼接一个主键
        $fields = "IFNULL(b.eid, concat(a.branch_id,'_',a.product_id)) AS eid, b.store_sell_type,b.sell_delay,b.sell_end_time,b.sync_status, a.branch_id,a.product_id,a.store,a.store_freeze";
        $sql = "SELECT count(a.product_id) as _count FROM sdb_ome_branch_product AS a LEFT JOIN sdb_ome_branch_product_extend AS b ON (a.branch_id=b.branch_id AND a.product_id=b.product_id) ";
        $sql .= " WHERE 1 ". $strWhere ." ORDER BY a.product_id, a.branch_id DESC";
        
        $row = $this->db->selectrow($sql);
        
        return intval($row['_count']);
    }
    
    function getlists($cols='*', $filter=array(), $offset=0, $limit=-1, $orderby=null)
    {
        $strWhere = '';
        
        //仓库号
        if(isset($filter['branch_id']) && $filter['branch_id']){
            if (is_array($filter['branch_id'])){
                $strWhere .= ' AND a.branch_id IN ('.implode(',', $filter['branch_id']).') ';
            }else {
                $strWhere .= ' AND a.branch_id = '.$filter['branch_id'];
            }
        }
        
        //货号
        if(isset($filter['bn']) && $filter['bn']!=''){
            $filter['bn'] = str_replace(array('"', "'"), '', $filter['bn']);
            $sel_sql = "SELECT bm_id FROM sdb_material_basic_material WHERE material_bn='". $filter['bn'] ."'";
            $materialInfo = $this->db->selectrow($sel_sql);
            if($materialInfo){
                $strWhere .= " AND a.product_id=". $materialInfo['bm_id'] ." ";
            }else{
                $strWhere .= " AND a.product_id=-1 "; //不存在的货号
            }
        }
        
        //真实库存
        if(isset($filter['actual_store']) && $filter['actual_store']!=''){
            $strWhere .= ' AND a.store';
            if($filter['_actual_store_search']=='nequal'){
                $strWhere .= ' =';
            }else if($filter['_actual_store_search']=='than'){
                $strWhere .= ' >';
            }else if($filter['_actual_store_search']=='lthan'){
                $strWhere .= ' <';
            }
            $strWhere .= intval($filter['actual_store']);
        }
        
        //可用库存
        if(isset($filter['enum_store']) && $filter['enum_store']!=''){
            $strWhere .= ' AND a.store_freeze';
            if($filter['_enum_store_search']=='nequal'){
                $strWhere .= ' =';
            }else if($filter['_enum_store_search']=='than'){
                $strWhere .= ' >';
            }else if($filter['_enum_store_search']=='lthan'){
                $strWhere .= ' <';
            }
            $strWhere .= intval($filter['enum_store']);
        }
        
        //sdb_ome_branch_product没有唯一主键,通过branch_id+'_'+a.product_id拼接一个主键
        $fields = "IFNULL(b.eid, concat(a.branch_id,'_',a.product_id)) AS eid, b.store_sell_type,b.sell_delay,b.sell_end_time,b.sync_status, a.branch_id,a.product_id,a.store,a.store_freeze";
        $sql = "SELECT ". $fields ." FROM sdb_ome_branch_product AS a LEFT JOIN sdb_ome_branch_product_extend AS b ON (a.branch_id=b.branch_id AND a.product_id=b.product_id) ";
        $sql .= " WHERE 1 ". $strWhere ." ORDER BY a.product_id, a.branch_id DESC";
        
        $data = $this->db->selectLimit($sql, $limit, $offset);
        
        return $data;
    }
}
?>
