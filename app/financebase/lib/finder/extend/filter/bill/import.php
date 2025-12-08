<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class financebase_finder_extend_filter_bill_import{
    function get_extend_colums(){

        $db['bill_import']=array (
            'columns' => array (
//                'create_time' => array (
//                    'type' => 'time',
//                    'label' => '导入时间',
//                    'comment' => '导入时间',
//                    'editable' => false,
//                    'filtertype' => 'time',
//                    'filterdefault' => true,
//                    'in_list' => true,
//                    'panel_id' => 'billimport_finder_top',
//                ),

                'type' => array (
                     'type' =>array(
                            'order' => '单号',
                            'sku' => 'sku',
                            'sale' => '销售周期',
                     ),
                    'label' => '模板类型',
                    'comment' => '模板类型',
                    'editable' => false,
                    'filtertype' => 'time',
                    'filterdefault' => true,
                    'in_list' => true,
                    'panel_id' => 'billimport_finder_top',
                ),

                'file_name' => array (
                    'type' => 'varchar(255)',
                    'label' => '导入文件名',
                    'comment' => '导入文件名',
                    'editable' => false,
                    'filtertype' => 'time',
                    'filterdefault' => true,
                    'in_list' => true,
                    'panel_id' => 'billimport_finder_top',
                ),
            )
        );
        return $db;
    }
}
