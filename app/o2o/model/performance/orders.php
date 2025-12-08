<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_mdl_performance_orders extends dbeav_model
{
    function __construct($app){
        parent::__construct(app::get('ome'));
    }
    
    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */
    public function table_name($real=false){
        $table_name = 'orders';
        if($real){
            return kernel::database()->prefix.$this->app->app_id.'_'.$table_name;
        }else{
            return $table_name;
        }
    }

    /**
     * object_name
     * @return mixed 返回值
     */
    public function object_name(){
        return 'performance_orders';
    }

    function searchOptions(){
        $options = array(
            'order_bn' => '订单号',
            'member_uname'=>'用户名',
            'ship_name'=>'收货人',
            'ship_tel_mobile'=>'联系电话',
            'product_bn'=>'货号',
        );
        return $options;
    }

    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter,$tableAlias=null,$baseWhere=null){

        if (isset($filter['store_process_status'])){
            $where .= ' AND order_id IN (SELECT order_id FROM sdb_ome_order_extend WHERE store_process_status="' . $filter['store_process_status'] . '")';
            // $orderExtendObj = app::get('ome')->model("order_extend");
            // $rows = $orderExtendObj->getList('order_id',array('store_process_status'=>$filter['store_process_status']));
            // $orderId[] = 0;
            // foreach($rows as $row){
            //     $orderId[] = $row['order_id'];
            // }

            // if($orderId)
            // $where .= '  AND order_id IN ('.implode(',', $orderId).')';
            unset($filter['store_process_status']);
        }

        if (isset($filter['member_uname'])){
            $memberObj = app::get('ome')->model("members");
            $rows = $memberObj->getList('member_id',array('uname|has'=>$filter['member_uname']));
            $memberId[] = 0;
            foreach($rows as $row){
                $memberId[] = $row['member_id'];
            }
            $where .= '  AND member_id IN ('.implode(',', $memberId).')';
            unset($filter['member_uname']);
        }

        if(isset($filter['ship_tel_mobile'])){
            $where .= ' AND (ship_tel=\''.$filter['ship_tel_mobile'].'\' or ship_mobile=\''.$filter['ship_tel_mobile'].'\')';
            unset($filter['ship_tel_mobile']);
        }

        if(isset($filter['product_bn'])){
            $itemsObj = app::get('ome')->model("order_items");
            $rows = $itemsObj->getOrderIdByFilterbn($filter);
            $orderId[] = 0;
            foreach($rows as $row){
                $orderId[] = $row['order_id'];
            }
            $pkjrows = $itemsObj->getOrderIdByPkgbn($filter['product_bn']);
            foreach($pkjrows as $pkjrow){
                $orderId[] = $pkjrow['order_id'];
            }

            $where .= '  AND sdb_ome_orders.order_id IN ('.implode(',', $orderId).')';
            unset($filter['product_bn']);
        }

        return parent::_filter($filter,$tableAlias,$baseWhere).$where;
    }

    /**
     * modifier_is_cod
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function modifier_is_cod($row){
        if($row == 'true'){
            return "<div style='width:48px;padding:2px;height:16px;background-color:green;float:left;'><span style='color:#eeeeee;'>货到付款</span></div>";
        }else{
            return '款到发货';
        }
    }
}