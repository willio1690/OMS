<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['bbu'] = array(
    'columns' => array(
        'bbu_id'   => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'label'    => '自增ID',
        ),
        'bbu_code' => array(
            'type'            => 'varchar(32)',
            'required'        => true,
            'label'           => '品牌BU编码',
            'in_list'         => true,
            'default_in_list' => true,
            'searchtype'      => 'nequal',
            'filtertype'      => 'normal',
            'filterdefault'   => true,
            'order'           => 10,
        ),
        'bbu_name' => array(
            'type'            => 'varchar(50)',
            'label'           => '品牌BU名称',
            'editable'        => false,
            'is_title'        => true,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 20,
        ),
        'status'   => array(
            'type'            => "enum('active', 'close')",
            'label'           => '状态',
            'default'         => 'active',
            'in_list'         => false,
            'default_in_list' => false,
            'editable'        => false,
            'order'           => 30,
        ),
        'op_name'  => array(
            'type'            => 'varchar(32)',
            'editable'        => false,
            'label'           => '创建者', // 取账号名
            'width'           => 90,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 60,
        ),
        'cos_id'   => array(
            'type'     => 'table:cos@organization',
            'label'    => '组织架构ID',
            'editable' => false,
        ),
        'at_time'  => array(
            'type'            => 'TIMESTAMP',
            'label'           => '创建时间',
            'default'         => 'CURRENT_TIMESTAMP',
            'width'           => 150,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 40,
        ),
        'up_time'  => array(
            'type'            => 'TIMESTAMP',
            'label'           => '更新时间',
            'default'         => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'width'           => 150,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 50,
        ),
    ),
    'index'   => array(
        'ind_bbu_code' => array(
            'columns' => array(
                0 => 'bbu_code',
            ),
            'prefix'  => 'unique',
        ),
        'ind_cos_id'   => array(
            'columns' => array(
                0 => 'cos_id',
            ),
        ),
        'ind_status'   => array(
            'columns' => array(
                0 => 'status',
            ),
        ),
    ),
    'comment' => '销售团队表',
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
