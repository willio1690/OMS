<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class wms_mdl_basic_material_storage_life extends material_mdl_basic_material_storage_life
{
    public function table_name($real = false)
    {
        if($real){
            $table_name = 'sdb_material_basic_material_storage_life';
        }else{
            $table_name = 'basic_material_storage_life';
        }
        return $table_name;
    }
    
    public function get_schema()
    {
        return app::get('material')->model('basic_material_storage_life')->get_schema();
    }
}
