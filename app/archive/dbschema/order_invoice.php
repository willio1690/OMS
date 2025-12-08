<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['order_invoice'] = array(
    'columns' => array(
        'id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'editable' => false,
            'extra' => 'auto_increment',
        ),
        'order_id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'editable' => false,
            'label' => '订单ID',
        ),
        'tax_title' => array(
            'type' => 'varchar(255)',
            'label' => '发票抬头',
            'editable' => false,
        ),
        'tax_no' => array(
            'type' => 'varchar(255)',
            'label' => '发票号',
            'editable' => false,
        ),
        'register_no' => array(
            'type' => 'varchar(255)',
            'label' => '税务号',
            'editable' => false
        ),
        'invoice_kind' => array(
            'type' => array(
                0 => '纸质发票',
                1 => '电子普票',
                2 => '电子专票',
            ),
            'default' => '0',
        ),
        'title_type' => array(
            'type' => array(
                0 => '个人',
                1 => '企业',
            ),
            'default' => '0',
        ),
        'create_time' => array(
            'type' => 'time',
            'editable' => false,
            'label' => '创建时间'
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
        'ind_order_id' => array(
            'columns' => array(
                0 => 'order_id',
            ),
        ),
        'ind_create_time' => array(
            'columns' => array(
                0 => 'create_time'
            )
        ),
        'ind_archive_time' => array(
            'columns' => array(
                0 => 'archive_time'
            )
        ),
    ),
    'comment' => '归档订单发票记录',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
); 