<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_operation_log{
        function get_operations(){
           $operations = array(
             'inventory_cancel' => array('name'=> '门店盘点单作废','type' => 'inventory@o2o'),
             'inventory_edit' => array('name'=> '门店盘点单编辑','type' => 'inventory@o2o'),
             'inventory_confirm' => array('name'=> '门店盘点单确认','type' => 'inventory@o2o'),
             'delivery' => array('name'=> '单据操作','type' => 'delivery@o2o'),
             'delivery_expre' => array('name'=> '快递单打印','type' => 'delivery@o2o'),
             'delivery_deliv' => array('name'=> '发货单打印','type' => 'delivery@o2o'),
             'store_upsert'      => array('name' => '门店信息维护', 'type' => 'store@o2o'),
            'storage_upsert'    => array('name' => '门店仓位维护', 'type' => 'store@o2o'),
        );
        return array('o2o'=>$operations);
     }
}
?>