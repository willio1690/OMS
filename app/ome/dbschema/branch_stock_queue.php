<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['branch_stock_queue'] = array(
    'columns' => array(
        'id'        => array(
            'type'     => 'bigint unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'editable' => false,
            'label' => 'ID',
        ),
        'branch_id' => array (
            'type' => 'int(10)',
            'required' => true,
            'editable' => false,
            'label' => '仓库编码',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'product_id' =>  array (
            'type' => 'int(10)',
            'required' => true,
            'editable' => false,
            'label' => '基础物料编码',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'quantity' => array(
            'type' => 'int(10)',
            'default' => '0',
            'required' => true,
            'label' => '库存数量',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'status' => array(
            'type'  => array(
                '0'  => '未处理',
                '1'  => '处理中',
                '2'  => '处理完成', // 完成的会被删掉
                '3'  => '处理失败',
            ),
            'default' => '0',
            'required' => true,
            'label' => '处理状态',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'iostock_bn' => array (
            'type' => 'varchar(32)',
            'label' => '出入库单号',
            'default_in_list'=>true,
            'in_list'=>true,
            'width' => 125,
            'filtertype' => 'normal',
            'filterdefault' => true,
        ),
        'at_time'       => array(
            'type'    => 'TIMESTAMP',
            'label'   => '创建时间',
            'default' => 'CURRENT_TIMESTAMP',
            'width'   => 120,
            'in_list' => true,
        ),
        'up_time'       => array(
            'type'    => 'TIMESTAMP',
            'label'   => '更新时间',
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'width'   => 120,
            'in_list' => true,
        ),
    ),
    
    'index'   => array(
        'idx_branch_product' => array(
            'columns' => array(
                'branch_id',
                'product_id',
            ),
        ),
        'idx_status' => array(
            'columns' => array(
                'status',
            ),
        ),
        'idx_at_time' => array(
            'columns' => array(
                'at_time',
            ),
        ),
        'idx_up_time' => array(
            'columns' => array(
                'up_time',
            ),
        ),
    ),
    'comment' => '仓库存流水队列',
    'engine'  => 'innodb',
    'version' => '$Rev: 1',
);
