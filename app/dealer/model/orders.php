<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 经销订单model类
 *
 * @author wangbiao@shopex.cn
 * @version 2024.04.24
 */
class dealer_mdl_orders extends ome_mdl_orders
{
    function __construct($app)
    {
        parent::__construct(app::get('ome'));
    }
    
    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */

    public function table_name($real=false)
    {
        $table_name = 'orders';
        if($real){
            return kernel::database()->prefix.$this->app->app_id.'_'.$table_name;
        }else{
            return $table_name;
        }
    }
}