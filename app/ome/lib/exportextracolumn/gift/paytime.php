<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_exportextracolumn_gift_paytime extends ome_exportextracolumn_abstract implements ome_exportextracolumn_interface{

    protected $__pkey = 'order_id';

    protected $__extra_column = 'column_paytime';

    public function associatedData($ids){
        $mdl_ome_orders = app::get('ome')->model('orders');
        $orderData = $mdl_ome_orders->getList("order_id,paytime",array("order_id"=>$ids));
        $tmp_array = array();
        foreach ($orderData as $key => $row){
            $tmp_array[$row[$this->__pkey]] = date("Y-m-d H:i:s",$row["paytime"]);
        }
        return $tmp_array;
    }
    
}