<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 分销发货单model类
 *
 * @author wangbiao@shopex.cn
 * @version 2024.04.28
 */
class dealer_mdl_delivery extends console_mdl_delivery
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
        $table_name = 'delivery';
        if($real){
            return 'sdb_ome_delivery';
        }else{
            return $table_name;
        }
    }
}