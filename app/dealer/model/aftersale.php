<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 分销售后单model类
 *
 * @author wangbiao@shopex.cn
 * @version 2024.04.28
 */
class dealer_mdl_aftersale extends sales_mdl_aftersale
{
    
    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */

    public function table_name($real = false)
    {
        if($real){
           $table_name = 'sdb_sales_aftersale';
        }else{
           $table_name = 'aftersale';
        }
        
        return $table_name;
    }

    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema(){
        return app::get('sales')->model('aftersale')->get_schema();
    }

}
