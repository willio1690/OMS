<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_service_order_sales_giftpackage{
    
    /**
     * 计算礼包差额
     * @access public static
     * @param $order_objects objects_sdf 结构
     * @return Number 差额
     */
    public static function get_difference($order_objects){
        if (empty($order_objects)) return 0;
        
        $difference = kernel::single('ome_order_order')->obj_difference($order_objects);
        return $difference;
    }
    
}