<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_mdl_iso_type extends dbeav_model {

    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */
    public function table_name($real=false)
    {
        $table_name = 'iostock_type';
        if($real){
            return DB_PREFIX.'siso_'.$table_name;
        }else{
            return $table_name;
        }
    }
    
    //确定表结构
    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema(){
        return app::get('siso')->model('iostock_type')->get_schema();
    }
}