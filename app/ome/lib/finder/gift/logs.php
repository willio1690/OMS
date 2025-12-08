<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/11/9 14:37:01
 * @describe: 类
 * ============================
 */
class ome_finder_gift_logs {
    public $addon_cols = 'order_bn,shop_id';


    public $column_createtime = "下单时间";
    public $column_createtime_width = 120;
    /**
     * column_createtime
     * @param mixed $row row
     * @return mixed 返回值
     */

    public function column_createtime($row) {
        $mdl_ome_orders = app::get('ome')->model('orders');
        $order = $mdl_ome_orders->dump(array("order_bn"=>$row[$this->col_prefix . "order_bn"],"shop_id"=>$row[$this->col_prefix . "shop_id"]),"createtime");
        return $order['createtime'] ? date('Y-m-d H:i:s', $order['createtime']) : '-';
    }

    public $column_paytime = "付款时间";
    public $column_paytime_width = 120;
    /**
     * column_paytime
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_paytime($row) {
        $mdl_ome_orders = app::get('ome')->model('orders');
        $order = $mdl_ome_orders->dump(array("order_bn"=>$row[$this->col_prefix . "order_bn"],"shop_id"=>$row[$this->col_prefix . "shop_id"]),"paytime");
        return $order['paytime'] ? date('Y-m-d H:i:s', $order['paytime']) : '-';
    }

}