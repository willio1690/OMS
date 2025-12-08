<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['betc'] = array(
    'columns' => array(
        'betc_id'         => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'label'    => '自增ID',
        ),
        'betc_name'       => array(
            'type'            => 'varchar(50)',
            'label'           => '贸易公司名称',
            'editable'        => false,
            'searchtype'      => 'nequal',
            'is_title'        => true,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 20,
        ),
        'betc_code'       => array(
            'type'            => 'varchar(32)',
            'required'        => true,
            'label'           => '贸易公司编码',
            'in_list'         => true,
            'default_in_list' => true,
            'searchtype'      => 'nequal',
            // 'filtertype'      => 'normal',
            // 'filterdefault'   => true,
            'order'           => 10,
        ),
        'bbu_id'          => array(
            'type'            => 'table:bbu@dealer',
            'label'           => '所属品牌BU',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 30,
        ),
        'cos_id'          => array(
            'type'     => 'table:cos@organization',
            'label'    => '组织架构ID',
            'editable' => false,
        ),
        'contact_name'    => array(
            'type'            => 'varchar(30)',
            'label'           => '联系人',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 40,
        ),
        'contact_mobile'  => array(
            'type'            => 'varchar(30)',
            'label'           => '联系人手机',
            'editable'        => false,
            'in_list'         => false,
            'default_in_list' => false,
            'order'           => 50,
        ),
        'contact_address' => array(
            'type'            => 'varchar(255)',
            'label'           => '联系人地址',
            'editable'        => false,
            'in_list'         => false,
            'default_in_list' => false,
            'order'           => 60,
        ),
        'status'          => array(
            'type'            => "enum('active', 'close')",
            'label'           => '公司状态',
            'default'         => 'active',
            'in_list'         => false,
            'default_in_list' => false,
            'editable'        => false,
            'order'           => 70,
        ),
        'op_name'         => array(
            'type'            => 'varchar(32)',
            'editable'        => false,
            'label'           => '创建者', // 取账号名
            'width'           => 140,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 100,
        ),
        'at_time'         => array(
            'type'            => 'TIMESTAMP',
            'label'           => '创建时间',
            'default'         => 'CURRENT_TIMESTAMP',
            'width'           => 150,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 80,
        ),
        'up_time'         => array(
            'type'            => 'TIMESTAMP',
            'label'           => '更新时间',
            'default'         => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'width'           => 150,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 90,
        ),
    ),
    'index'   => array(
        'ind_betc_code'      => array(
            'columns' => array(
                0 => 'betc_code',
            ),
            'prefix'  => 'unique',
        ),
        'ind_bbu_id'         => array(
            'columns' => array(
                0 => 'bbu_id',
            ),
        ),
        'ind_cos_id'         => array(
            'columns' => array(
                0 => 'cos_id',
            ),
        ),
        'ind_contact_mobile' => array(
            'columns' => array(
                0 => 'contact_mobile',
            ),
        ),
        'ind_contact_name'   => array(
            'columns' => array(
                0 => 'contact_name',
            ),
        ),
        'ind_status'         => array(
            'columns' => array(
                0 => 'status',
            ),
        ),
    ),
    'comment' => '贸易公司表',
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
