<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_finder_extend_filter_product_serial_history{
    function get_extend_colums(){
        $db['product_serial_history']=array (
            'columns' => array (
                'act_type' =>
                array (
                    'type' => array(
                        '1' => '出库',
                        '2' => '退入',
                    ),
                    'label' => '操作类型',
                    'required' => true,
                    'default' => 0,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'filtertype' => 'yes',
                    'filterdefault' => true,
                ),
                'bill_type' =>
                array (
                    'type' => array(
                        '1' => '发货单',
                        '2' => '退货单',
                    ),
                    'label' => '单据类型',
                    'required' => true,
                    'default' => 0,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'filtertype' => 'yes',
                    'filterdefault' => true,
                ),
            )
        );
        return $db;
    }


}