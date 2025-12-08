<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class financebase_finder_extend_filter_bill_import_order{
    function get_extend_colums(){

//        http://www.qc.local/index.php/#app=financebase&ctl=admin_shop_settlement_cainiao&act=index&view=&page=
//http://www.qc.local/index.php/#app=financebase&ctl=admin_shop_settlement_cainiao&act=index&view=0
        $db['bill_import_order']=array (
            'columns' => array (

                'confirm_status' => array (
                     'type' =>array(
                            '0' => '未确认',
                            '1' => '已确认',
                     ),
                    'label' => '确认状态',
                    'comment' => '确认状态',
                     'filtertype' => 'time',
                    'editable' => false,
                    'filterdefault' => true,
                    'in_list' => true,
                    'panel_id' => 'importorder_finder_top',
                ),

                'pay_serial_number' => array (
                    'type' => 'varchar(500)',
                    'label' => '支付流水号',
                    'comment' => '支付流水号',
                    'filtertype' => 'time',
                    'editable' => false,
                    'filterdefault' => true,
                    'in_list' => true,
                    'panel_id' => 'importorder_finder_top',
                ),

            ),
        );
//p($db,1);
        return $db;
    }
}
