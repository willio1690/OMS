<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_service_order_remain_goods {
    
    /*
     * 获取订单编辑的商品类型配置列表
     * @return array conf
     */
    public function diff_money($obj){
        $amount = 0;
        if ($service = kernel::service("ome.service.order.object.diff.goods")){
            if (method_exists($service, 'diff_money')) $amount = $service->diff_money($obj);
        }
        return $amount;
        //return kernel::single("ome_order_remain_goods")->diff_money($obj);
    }

    /*
     * 余单撤销处理
     */
    public function remain_cancel($obj){
        return kernel::single("ome_order_remain_goods")->remain_cancel($obj);
    }
}