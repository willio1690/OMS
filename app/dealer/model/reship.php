<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 经销退货单model类
 *
 * @author
 * @version 2024.04.24
 */
class dealer_mdl_reship extends ome_mdl_reship
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
        $table_name = 'reship';
        if($real){
            return kernel::database()->prefix.$this->app->app_id.'_'.$table_name;
        }else{
            return $table_name;
        }
    }
}