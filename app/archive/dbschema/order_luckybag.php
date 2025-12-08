<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['order_luckybag'] = array(
    'columns' => array(
        'lid' => array(
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'editable' => false,
            'extra' => 'auto_increment',
        ),
        'order_id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'default' => 0,
            'editable' => false,
            'label' => '订单ID',
        ),
        'obj_id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'default' => 0,
            'editable' => false,
            'comment' => '订单子单ID,order_objects.obj_id'
        ),
        'item_id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'default' => 0,
            'editable' => false,
            'comment' => '订单item层明细ID'
        ),
        'combine_id' => array(
            'type' => 'int unsigned',
            'label' => '福袋组合ID',
            'required' => true,
        ),
        'combine_bn' => array(
            'type' => 'varchar(32)',
            'label' => '福袋组合编码',
            'editable' => true,
            'searchtype' => 'nequal',
            'filtertype' => 'normal',
            'filterdefault' => 'true',
            'in_list' => true,
            'default_in_list' => true,
            'width' => 180,
        ),
        'bm_id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'hidden' => true,
            'editable' => false,
            'comment' => '基础物料ID,关联material_basic_material.bm_id'
        ),
        'selected_number' => array(
            'type' => 'int unsigned',
            'required' => true,
            'hidden' => true,
            'editable' => false,
            'label' => '选中个数',
            'comment' => '选中个数',
        ),
        'include_number' => array(
            'type' => 'int unsigned',
            'required' => true,
            'hidden' => true,
            'editable' => false,
            'label' => '分配货品数量',
            'comment' => '分配货品数量',
        ),
        'real_ratio' => array(
            'type' => 'tinyint(3)',
            'editable' => false,
            'label' => '选中比例',
            'default' => 100,
            'hidden' => true,
        ),
        'price_rate' => array(
            'type' => 'decimal(5,2)',
            'editable' => false,
            'label' => '金额贡献占比',
            'default' => 100,
            'hidden' => true,
        ),
        'at_time' => array(
            'type' => 'TIMESTAMP',
            'label' => '创建时间',
            'default' => 'CURRENT_TIMESTAMP',
            'width' => 120,
        ),
        'up_time' => array(
            'type' => 'TIMESTAMP',
            'label' => '更新时间',
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'width' => 120,
        ),
        'archive_time' => array(
            'type' => 'int unsigned',
            'label' => '归档时间',
            'width' => 130,
            'editable' => false,
            'in_list' => true,
            'filtertype' => 'time',
            'filterdefault' => true,
        ),
    ),
    'index' => array(
        'ind_combine_id' => array(
            'columns' => array(
                0 => 'combine_id',
            ),
        ),
        'ind_bm_id' => array(
            'columns' => array(
                0 => 'bm_id',
            ),
        ),
        'ind_order_object' => array(
            'columns' => array(
                0 => 'order_id',
                1 => 'obj_id',
            ),
        ),
        'ind_order_item' => array(
            'columns' => array(
                0 => 'order_id',
                1 => 'item_id',
            ),
        ),
        'ind_at_time' => array(
            'columns' => array(
                0 => 'at_time',
            ),
        ),
        'ind_up_time' => array(
            'columns' => array(
                0 => 'up_time',
            ),
        ),
        'ind_archive_time' => array(
            'columns' => array(
                0 => 'archive_time',
            ),
        ),
    ),
    'comment' => '归档订单福袋表',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
); 