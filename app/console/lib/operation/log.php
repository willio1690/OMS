<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_operation_log{
        
    /**
     * 定义当前APP下的操作日志的所有操作名称列表
     * type键值由表名@APP名称组成
     * @access public
     * @return Array
     */
    function get_operations(){
        $operations = array(
           //'purchase_create' => array('name'=> '生成采购单','type' => 'purchase@console'),

           'wms_reship' => array('name'=> '第三方退货单','type' => 'wms_reship@console'),
           'wms_delivery' => array('name'=> '第三方发货单','type' => 'wms_delivery@console'),
           'wms_stockin' => array('name'=> '第三方入库单','type' => 'wms_stockin@console'),
           'wms_stockout' => array('name'=> '第三方出库单','type' => 'wms_stockout@console'),
           'wms_storeprocess' => array('name'=> '第三方加工单','type' => 'wms_storeprocess@console'),
           'wms_transferorder' => array('name'=> '第三方转储单','type' => 'wms_transferorder@console'),
           'inventory_apply' => array('name'=> '盘点申请单','type' => 'inventory_apply@console'),
           'difference' => array('name'=> '差异单','type' => 'difference@console'),
           'material_package' => array('name'=> '加工单','type' => 'material_package@console'),
            'vopreturn' => array('name'=> '唯品会退供单','type' => 'vopreturn@console'),
            'vopbill' => array('name'=> '唯品会账单','type' => 'vopbill@console'),
            'adjust' => array('name'=> '库存调整单','type' => 'adjust@console'),
            'replenish_suggest' => array('name'=> '补货建议','type' => 'replenish_suggest@console'),
           'other_iostock_cancel' => array('name'=> '单据取消','type' => 'iso@taoguaniostockorder'),
        );
        
        return array('console'=>$operations);
    }
}
?>