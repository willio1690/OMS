<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_finder_extend_filter_vopstockout{
    function get_extend_colums(){
        
        //入库仓列表
        $purchaseLib  = kernel::single('purchase_purchase_order');
        $shopList     = $purchaseLib->getWarehouse();
        
        $warehouseList = array();
        if($shopList){
            foreach ($shopList as $key => $val){
                $warehouseList[$val['branch_bn']] = $val['branch_name'];
            }
        }
        
        //单据状态
        $stockoutLib  = kernel::single('purchase_purchase_stockout');
        $statusList = $stockoutLib->getBillStatus();
        $ostatusList = $stockoutLib->getStockoutStatus();
        $confirm_status = array(1=>'未审核', '已审核', '取消');
        
        $db['pick_stockout_bills']=array (
            'columns' => array (
                    'to_branch_bn' =>
                    array (
                            'type' => $warehouseList,
                            'label' => '入库仓',
                            'width' => 120,
                            'editable' => false,
                            'order' => 20,
                            'in_list' => true,
                            'default_in_list' => true,
                            'filtertype' => true,
                            'filterdefault' => true,
                    ),
                    'status' =>
                    array (
                            'type' => $statusList,
                            'label' => '单据状态',
                            'width' => 130,
                            'editable' => false,
                            'default' => 'false',
                            'order' => 11,
                            'filtertype' => true,
                            'filterdefault' => true,
                    ),
                    'confirm_status' =>
                    array (
                            'type' => $confirm_status,
                            'label' => '审核状态',
                            'width' => 130,
                            'editable' => false,
                            'default' => 'false',
                            'order' => 12,
                            'filtertype' => true,
                            'filterdefault' => true,
                    ),
                    'o_status' =>
                    array (
                            'type' => $ostatusList,
                            'label' => '出库状态',
                            'width' => 100,
                            'editable' => false,
                            'default' => 'false',
                            'order' => 13,
                            'filtertype' => true,
                            'filterdefault' => true,
                    ),
                    'order_label' => 
                    array (
                            'type' => 'table:order_labels@omeauto',
                            'label' => '标记',
                            'width' => 120,
                            'filtertype' => 'normal',
                            'filterdefault' => true,
                            'editable' => false,
                            'in_list' => true,
                            'default_in_list' => true,
                    ),
            )
        );
        return $db;
    }
}

