<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['aoxiang_branch'] = array(
    'columns' => array(
        'bid' => array( 
            'pkey' => 'true',
            'type' => 'int unsigned',
            'extra' => 'auto_increment',
            'label' => '映射ID',
            'order' => 1,
        ),
        'shop_id' => array(
            'type' => 'table:shop@ome',
            'label' => '来源店铺',
            'width' => 120,
            'editable' => false,
            'filtertype' => 'yes',
            'filterdefault' => true,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 2,
        ),
        'shop_type' => array(
            'type' => 'varchar(50)',
            'label' => '店铺类型',
            'width' => 90,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => false,
            'order' => 3,
        ),
        'branch_id' => array (
            'type' => 'table:branch@ome',
            'label' => '仓库',
            'required' => false,
            'editable' => false,
            'filtertype' => 'yes',
            'filterdefault' => true,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 10,
        ),
        'branch_bn' => array(
            'type' => 'varchar(32)',
            'label' => '仓库编码',
            'editable' => true,
            'searchtype' => 'nequal',
            'filtertype' => 'normal',
            'filterdefault' => 'true',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 12,
        ),
        'create_time' => array (
            'type' => 'time',
            'editable' => false,
            'label' => '创建时间',
            'in_list' => true,
            'default_in_list' => true,
            'filtertype' => 'time',
            'filterdefault' => true,
            'order' => 98,
        ),
        'last_modified' => array (
            'type' => 'time',
            'editable' => false,
            'label' => '最后修改时间',
            'in_list' => true,
            'default_in_list' => true,
            'filtertype' => 'time',
            'filterdefault' => true,
            'order' => 99,
        ),
        'sync_status' => array (
            'type' => array(
                'none' => '未同步',
                'fail' => '同步失败',
                'succ' => '同步成功',
            ),
            'default' => 'none',
            'label' => '同步状态',
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'filtertype' => 'yes',
            'filterdefault' => true,
            'width' => 120,
            'order' => 80,
        ),
        'sync_msg' => array (
            'type' => 'text',
            'label' => '同步失败原因',
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 82,
        ),
        'fail_nums' => array (
            'type' => 'number',
            'default' => 0,
            'label' => '失败次数',
            'width' => 90,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 84,
        ),
        'seller_id' => array(
            'type' => 'varchar(32)',
            'label' => 'seller_id',
            'editable' => true,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 80,
        ),
    ),
    'index' => array(
        'ind_shop_sync' => array(
            'columns' => array(
                0 => 'shop_id',
                1 => 'sync_status',
            ),
        ),
        'ind_branch_bn' => array(
            'columns' => array(
                0 => 'branch_bn',
            ),
        ),
        'ind_create_time' => array (
            'columns' => array (
                0 => 'create_time',
            ),
        ),
        'ind_last_modified' => array(
            'columns' => array(
                0 => 'last_modified',
            ),
        ),
        'ind_shop_branch' => array(
            'columns' => array(
                0 => 'shop_id',
                1 => 'branch_id',
            ),
        ),
    ),
    'engine' => 'innodb',
    'version' => '$Rev: $',
    'comment' => '仓库映射表',
);