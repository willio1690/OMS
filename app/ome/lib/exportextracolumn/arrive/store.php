<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_exportextracolumn_arrive_store extends ome_exportextracolumn_abstract implements ome_exportextracolumn_interface{

    protected $__pkey = 'bm_id';

    protected $__extra_column = 'column_arrive_store';

    public function associatedData($ids){
        $lib_mbmsf = kernel::single('material_basic_material_stock_freeze');
        $tmp_array = array();
        foreach ($ids as $var_bm_id){
            $tmp_array[$var_bm_id] = $lib_mbmsf->getMaterialArriveStore($var_bm_id);
        }
        return $tmp_array;
    }

}