<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class inventorydepth_mdl_products extends dbeav_model{

    public function table_name($real=false)
    {
        $table_name = 'sales_material';
        if($real){
            return kernel::database()->prefix.app::get('material')->app_id.'_'.$table_name;
        }else{
            return $table_name;
        }
    }
    
    public function get_schema()
    {
        return app::get('material')->model('sales_material')->get_schema();
    }
}
?>