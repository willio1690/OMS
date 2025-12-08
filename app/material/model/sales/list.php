<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class material_mdl_sales_list extends material_mdl_sales_material
{
    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */
    public function table_name($real = false)
    {
        $table_name = 'sales_material';
        if ($real) {
            return kernel::database()->prefix . $this->app->app_id . '_' . $table_name;
        } else {
            return $table_name;
        }
    }

    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema()
    {
        $schema = parent::get_schema();
        $mySchema = array(
            0 => array(
                'name' => '仓库名',
                'column' => 'name'
            ),
            1 => array(
                'name' => '库存',
                'column' => 'store'
            ),
        );
        foreach ($schema['columns'] as $key => &$value) {
            if (isset($value['pkey'])) {
                unset($value['pkey']);
            }
        }
        foreach ($mySchema as $key => $value) {
            $index = 'fields_'.$value['column'];
            $schema['columns'][$index] = array (
                'type' => 'varchar(30)',
                'label' => $value['name'],
                'fields_info' => $value,
                'order' => '1' . $key,
                'orderby' => false
            );
            $schema['default_in_list'][] = $index;
            $schema['in_list'][] = $index;
        }

        return $schema;
    }

    public function getlist($cols='*', $filter=array(), $offset=0, $limit=-1, $orderby=null) {
        if (strpos($cols, 'fields_') !== false) {
            $fields = explode(',', $cols);
            foreach ($fields as $key => $value) {
                $value = str_replace('`', '', $value);
                if($this->schema['columns'][$value]['fields_info']) {
                    if ($this->schema['columns'][$value]['fields_info']['column'] == 'store') {
                        $fields[$key] = 'sobp.'.$this->schema['columns'][$value]['fields_info']['column'].' as '.$value;
                    }else {
                        $fields[$key] = 'sob.'.$this->schema['columns'][$value]['fields_info']['column'].' as '.$value;
                    }
                }
                if ($value == 'sm_id') {
                    $fields[$key] = 'smsm.'.$value;
                }
            }
            $cols = implode(',', $fields);
            $cols .= ',sob.branch_id';
        }
       
        if (isset($filter['plimit'])) {
            $limit = $filter['plimit'];
            unset($filter['plimit']);
        }
        foreach ($filter as $key => $value) {
            if (is_array($value)) {
                $where .= ' and smsm.'.$key.' in (\''.implode("','", $value).'\')';
            } else {
                $where .= ' and smsm.'.$key.' = \''.$value.'\'';
            }
        }
        $sql = 'select '.$cols.'
from sdb_material_sales_material smsm
left join sdb_material_sales_basic_material smsbm on smsm.sm_id = smsbm.sm_id
left join sdb_material_basic_material smbm on smbm.bm_id = smsbm.bm_id
left join sdb_ome_branch_product sobp on sobp.product_id = smbm.bm_id
left join sdb_ome_branch sob on sob.branch_id = sobp.branch_id
where sobp.product_id is not null and sobp.store != 0 '.$where.' 
group by smsm.sm_id, sob.name';
        $branchRows = $this->db->selectLimit($sql,$limit,$offset);
        
        foreach ($branchRows as $k => $v) {
            $branchRows[$k]['sm_id'] .= '_'.$v['branch_id'];
        }
        return $branchRows;
    }
    
    /**
     * count
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function count($filter = null) {
        $where = '';
        if (isset($filter['shop_id'])) {
            $where .= ' and smsm.shop_id in (\''.implode("','", $filter['shop_id']).'\')';
        }
        if (isset($filter['is_bind'])) {
            $where .= ' and smsm.is_bind = '.$filter['is_bind'];
        }
        $sql = 'select count(*) as _count from(select count(*)
from sdb_material_sales_material smsm
         left join sdb_material_sales_basic_material smsbm on smsm.sm_id = smsbm.sm_id
         left join sdb_material_basic_material smbm on smbm.bm_id = smsbm.bm_id
         left join sdb_ome_branch_product sobp on sobp.product_id = smbm.bm_id
         left join sdb_ome_branch sob on sob.branch_id = sobp.branch_id
where sobp.product_id is not null
  and sobp.store != 0
  '.$where.'
group by smsm.sm_id, sob.name) s';
        $row = $this->db->select($sql);
        return intval($row[0]['_count']);
    }
}