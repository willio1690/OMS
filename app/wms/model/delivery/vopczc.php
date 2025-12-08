<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author sunjing
 * @describe 唯品会仓中仓
 */
class wms_mdl_delivery_vopczc extends ome_mdl_delivery {
    public $has_export_cnf = false;

    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */

    public function table_name($real=false)
    {
        $table_name = 'delivery';
        if($real){
            return DB_PREFIX.'wms_'.$table_name;
        }else{
            return $table_name;
        }
    }

    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter, $tableAlias = null, $baseWhere = null)
    {
        $filter['type'] = 'vopczc';
        return parent::_filter($filter, $tableAlias, $baseWhere);
    }
}