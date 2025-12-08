<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_service_order_products{
    
    /*
     * 获取订单编辑的商品类型配置列表
     * @return array conf
     */
    public function view_list(){
        return kernel::single("ome_order_products")->get_view_list();
    }
    
}