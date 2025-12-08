<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 发票列表的扩展字段付款状态
 * 20160712
 * @author wangjianjun@shopex.cn
 * @version 1.0
 */
class invoice_extracolumn_order_paystatus extends invoice_extracolumn_abstract implements invoice_extracolumn_interface{

    protected $__pkey = 'id';

    protected $__extra_column = 'column_pay_status';

    /**
     * 获取发票列表页记录的相关订单的付款状态
     * @param $ids
     * @return array $tmp_array关联数据数组
     */
    public function associatedData($ids){
        //根据主键id拿不重复的order_id
        $mdlInOrder = app::get('invoice')->model('order');
        $rs_invoice = $mdlInOrder->getList("id,order_id",array("id|in"=>$ids));
        $order_ids = array();
        $rl_id_orderid = array(); //主键id和order_id之间的键值关系
        foreach ($rs_invoice as $var_invoice){
            if(!in_array($var_invoice["order_id"], $order_ids)){
                $order_ids[] = $var_invoice["order_id"];
            }
            $rl_id_orderid[$var_invoice["id"]] = $var_invoice["order_id"];
        }
        
        //获取付款状态的数据表枚举关系
        $mdlOmeOrders = app::get('ome')->model('orders');
        $columns = $mdlOmeOrders->schema;
        
        //同一获取订单的付款状态
        $rs_orders = $mdlOmeOrders->getList("order_id,pay_status",array("order_id|in"=>$order_ids));
        $rl_orderid_paystatus = array(); //order_id和付款状态的键值关系
        foreach ($rs_orders as $var_order){
            $rl_orderid_paystatus[$var_order["order_id"]] = $columns["columns"]["pay_status"]["type"][$var_order["pay_status"]];
        }
        
        //获取最终的返回数组
        $return_arr = array();
        foreach ($rl_id_orderid as $key_id=>$value_order_id){
            $return_arr[$key_id] = $rl_orderid_paystatus[$value_order_id];
        }
        
        return $return_arr;
    }

}