<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['inventory_snapshot_items'] = [
    'columns' => [
        'id'             => [
            'type'     => 'int unsigned',
            'label'    => 'ID',
            'comment'  => 'ID',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
        ],
        'invs_id'        => [
            'type'     => 'int unsigned',
            'label'    => '快照ID',
            'required' => true,
            'order'    => 10,
        ],
        'stock_date'     => [
            'type'            => 'DATE',
            // 'required'        => true,
            'label'           => '记录日期',
            'default_in_list' => true,
            'in_list'         => true,
            'order' => 10,
        ],
        'store_id'       => [
            'type'  => 'int unsigned',
            'label' => '门店ID',
            // 'required' => true,
        ],
        'item_code'      => [
            'type'            => 'varchar(32)',
            'label'           => '商品编码',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 10,
            'filtertype'    => 'normal',
            'filterdefault' => true,
            'searchtype'      => 'nequal',
        ],
        'store_bn'       => [
            'type'            => 'varchar(20)',
            'label'           => '门店编码',
            // 'required'        => true,
            'searchtype'      => 'nequal',
            'in_list'         => true,
            'default_in_list' => true,
            'order' => 20,
            'filtertype'    => 'normal',
            'filterdefault' => true,
        ],
        'branch_bn'      => [
            'type'            => 'varchar(32)',
            'label'           => '仓库编号',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 30,
            'filtertype'    => 'normal',
            'filterdefault' => true,
        ],
        'branch_id'      => [
            'type'            => 'mediumint unsigned',
            'label'           => '仓库ID',
        ],
        'item_id'        => [
            'type'            => 'varchar(32)',
            'label'           => '商品ID',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 50,
        ],
        'inventory_type' => [
            'type'            => 'varchar(32)',
            'label'           => '商品类型',
        ],
        'quantity'       => [
            'type'            => 'int',
            'label'           => '数量',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 60,
            'filtertype'    => 'number',
            'filterdefault' => true,
        ],
        'lock_quantity'  => [
            'type'            => 'int',
            'label'           => '冻结数量',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 70,
            'filtertype'    => 'number',
            'filterdefault' => true,
        ],
        'batch_code'     => [
            'type'            => 'varchar(32)',
            'label'           => '批次编码',
        ],
        'produce_code'   => [
            'type'            => 'varchar(32)',
            'label'           => '生产编号',
        ],
        'product_date'   => [
            'type'            => 'varchar(32)',
            'label'           => '商品生产日期',
        ],
        'expire_date'    => [
            'type'            => 'varchar(32)',
            'label'           => '商品过期日期',
        ],
        'at_time'        => [
            'type'            => 'TIMESTAMP',
            'label'           => '创建时间',
            'default'         => 'CURRENT_TIMESTAMP',
            'width'           => 120,
            'in_list'         => true,
        ],
        'up_time'        => [
            'type'            => 'TIMESTAMP',
            'label'           => '更新时间',
            'default'         => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'width'           => 120,
            'in_list'         => true,

        ],
    ],
    'index'   => [
        'ind_branch_bn'  => ['columns' => ['branch_bn']],
        'ind_item_code'  => ['columns' => ['item_code']],
        'ind_invs_id'    => ['columns' => ['invs_id']],
        'ind_stock_date' => ['columns' => ['stock_date', 'branch_id', 'item_code']],
    ],
    'comment' => 'POS库存快照明细',
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
];
