<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['useful_life_log']=array (
    'columns' =>
        array (
            'life_log_id' =>
                array (
                    'type' => 'bigint unsigned',
                    'required' => true,
                    'pkey' => true,
                    'extra' => 'auto_increment',
                ),
            'life_id' =>
                array (
                    'type' => 'table:useful_life@console',
                    'default' => 0,
                    'label' => '购买批次',
                    'width' => 125,
                ),
            'product_id' =>
                array (
                    'type' => 'table:basic_material@material',
                    'required' => true,
                    'default_in_list'=>true,
                    'in_list'=>true,
                    'label' => '基础物料',
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                ),
            'bn' =>
                array (
                    'type' => 'varchar(60)',
                    'label' => '基础物料编码',
                    'default_in_list'=>true,
                    'in_list'=>true,
                    'width' => 150,
                    'editable' => false,
                    'searchtype'      => 'nequal',
                ),
            'branch_id' =>
                array (
                    'type' => 'table:branch@ome',
                    // 'required' => true,
                    'label' => '仓库名称',
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'default_in_list'=>true,
                    'in_list'=>true,
                ),
            'sourcetb' =>
                array (
                    'type' => 'varchar(32)',
                    'label' => '来源表',
                ),
            'original_bn' =>
                array (
                    'type' => 'varchar(32)',
                    'label' => '原始单据号',
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'default_in_list'=>true,
                    'in_list'=>true,
                    'searchtype'      => 'nequal',
                ),
            'original_id' =>
                array (
                    'type' => 'int unsigned',
                    'comment' => '原始单据id',
                ),
            'create_time' =>
                array (
                    'type' => 'time',
                    'filtertype' => 'time',
                    'filterdefault' => true,
                    'default_in_list'=>true,
                    'in_list'=>true,
                    'label' => '创建时间',
                ),
            'stock_time' =>
                array (
                    'type' => 'time',
                    'default_in_list'=>true,
                    'in_list'=>true,
                    'label' => '出入库时间',
                ),
            'type_id' =>
                array (
                    'type' => 'number',
                    'label' => '出入库类型',
                    'default_in_list'=>true,
                    'in_list'=>true,
                ),
            'stock_status' =>
                array (
                    'type' => array(
                        '0' => '未出/入库',
                        '1' => '已出/入库',
                    ),
                    'label' => '出入库状态',
                    'default' => '0',
                    'required' => true,
                    'default_in_list'=>true,
                    'in_list'=>true,
                ),
            'num' =>
                array (
                    'type' => 'number',
                    'label' => '出入数量',
                    'required' => true,
                    'default_in_list'=>true,
                    'in_list'=>true,
                ),
            'normal_defective' =>
                array (
                    'type' => array(
                        'normal' => '正品',
                        'defective' => '残品',
                    ),
                    'label' => '正/残品',
                    'default' => 'normal',
                    'required' => true,
                    'default_in_list'=>true,
                    'in_list'=>true,
                ),
            'product_time' =>
                array (
                    'type' => 'time',
                    'label' => '生产时间',
                    'default_in_list'=>true,
                    'in_list'=>true,
                ),
            'expire_time' =>
                array (
                    'type' => 'time',
                    'label' => '到期时间',
                    'default_in_list'=>true,
                    'in_list'=>true,
                ),
            'purchase_code' =>
                array (
                    'type' => 'varchar(64)',
                    'label' => '购买批次',
                    'default' => '',
                    'default_in_list'=>true,
                    'in_list'=>true,
                    'searchtype'      => 'nequal',
                ),
            'produce_code' =>
                array (
                    'type' => 'varchar(32)',
                    'label' => '生产批次',
                    'default' => '',
                    'default_in_list'=>true,
                    'in_list'=>true,
            ),

            'bill_type'          => array(
                'type'            => 'varchar(50)',

                'label'           => '业务类型',
                'comment'         => '业务类型',
                'default' => '',  
                'in_list'         => true,
                'default_in_list' => false,
            ),
            'business_bn'         => array(
                'type'            => 'varchar(50)',
                'label'           => '业务单号',
                'default_in_list' => true,
                'in_list'         => true,
                'searchtype'      => 'nequal',
            ),

        ),
    'index' =>
        array (
            'ind_purchase_code' =>
                array (
                    'columns' =>
                        array (
                            0 => 'purchase_code',
                        ),
                ),
            'ind_original_bn' =>
                array (
                    'columns' =>
                        array (
                            0 => 'original_bn',
                        ),
                ),
            'ind_original_id' =>
                array (
                    'columns' =>
                        array (
                            0 => 'original_id',
                        ),
                ),
            'ind_create_time' =>
                array (
                    'columns' =>
                        array (
                            0 => 'create_time',
                        ),
                ),
                'ind_bn' =>
                array (
                    'columns' =>
                        array (
                            0 => 'bn',
                        ),
                ),
        ),
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);