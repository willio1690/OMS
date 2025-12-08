<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class financebase_finder_extend_filter_bill_import_sale
{
    function get_extend_colums()
    {

        $db['bill_import_sale'] = array(
            'columns' => array(

                'confirm_status' => array(
                    'type'       => array(
                        '0' => '未确认',
                        '1' => '已确认',
                    ),
                    'label'      => '确认状态',
                    'comment'    => '确认状态',
                    'editable'   => false,
                    'filtertype' => 'time',

                    'filterdefault' => true,
                    'in_list'       => true,
                    'panel_id'      => 'importsale_finder_top',
                ),

                'pay_serial_number' => array(
                    'type'          => 'varchar(500)',
                    'label'         => '支付流水号',
                    'comment'       => '支付流水号',
                    'filtertype'    => 'time',
                    'editable'      => false,
                    'filterdefault' => true,
                    'in_list'       => true,
                    'panel_id'      => 'importsale_finder_top',
                ),
            )
        );
        return $db;
    }
}
