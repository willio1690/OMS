<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class purchase_operation_log{
	    
    /**
     * 定义当前APP下的操作日志的所有操作名称列表
     * type键值由表名@APP名称组成
     * @access public
     * @return Array
     */
    function get_operations(){
        $operations = array(
           'purchase_create' => array('name'=> '生成采购单','type' => 'po@purchase'),
           'purchase_modify' => array('name'=> '修改采购单','type' => 'po@purchase'),
           'purchase_cancel' => array('name'=> '采购单入库取消','type' => 'po@purchase'),
           'purchase_storage' => array('name'=> '采购入库','type' => 'po@purchase'),
           'purchase_refund' => array('name'=> '采购退款','type' => 'purchase_refunds@purchase'),
           'purchase_delete' => array('name'=> '删除采购单','type' => 'po@purchase'),
           'purchase_shiftdelete' => array('name'=> '彻底删除采购单','type' => 'po@purchase'),
           'purchase_restore' => array('name'=> '恢复被删除的采购单','type' => 'po@purchase'),
           'purchase_supplier_del' => array('name'=> '删除供应商','type' => 'supplier@purchase'),
          'purchase_order_wait' => array('name'=> '待寻仓订单','type' => 'order_wait@purchase'),
        );
        
        return array('purchase'=>$operations);
    }
}
?>