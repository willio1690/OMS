<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 唯品会承运商mdl类
 *
 * @access public
 * @author wangbiao<wangbiao@shopex.cn>
 */
class console_mdl_carrier extends dbeav_model{
    
    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */

    public function table_name($real = false)
    {
        if($real){
           $table_name = 'sdb_purchase_carrier';
        }else{
           $table_name = 'carrier';
        }
        
        return $table_name;
    }
    
    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema(){
        return app::get('purchase')->model('carrier')->get_schema();
    }
}
?>
