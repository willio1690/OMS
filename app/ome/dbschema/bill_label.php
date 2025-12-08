<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['bill_label'] = array(
    'columns' => array(
        'id'          => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'editable' => false,
            'extra'    => 'auto_increment',
        ),
        'bill_type'   => array(
            'type'            => 'varchar(32)',
            'label'           => '单据类型',
            'width'           => 75,
            'hidden'          => true,
            'editable'        => false,
            'in_list'         => false,
            'default_in_list' => false,
        ),
        'bill_id'     => array(
            'type'            => 'varchar(32)',
            'default'         => 0,
            'editable'        => false,
            'label'           => '单据ID',
            'width'           => 120,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 1,
        ),
        'label_id'    => array(
            'type'            => 'int(10)',
            'default'         => 0,
            'editable'        => false,
            'label'           => '标记ID',
            'width'           => 90,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 10,
        ),
        'label_name'  => array(
            'type'            => 'varchar(30)',
            'editable'        => false,
            'label'           => '标记名称',
            'width'           => 120,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 12,
        ),
        'label_code' => array(
            'type' => 'varchar(30)',

            'editable' => false,

            'label' => '标签代码',

        ),
        'label_value' => array(
            'type'            => 'bigint(20)',
            'label'           => '标记明细',
            'editable'        => false,
            'default'         => '0',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'label_desc' => array (
            'type' => 'varchar(150)',
            'editable' => false,
            'label'  => '标记描述',
            'width' => 120,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 12,
        ),
        'extend_info' => array(
            'type' => 'text',
            'editable' => false,
            'label' => 'JSON简易扩展信息',
            'in_list' => false,
            'default_in_list' => false,
            'order' => 90,
        ),
        'create_time' => array(
            'type'            => 'time',
            'label'           => '创建时间',
            'width'           => 130,
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 98,
        ),
    ),
    'index'   => array(
        'in_bill'     => array(
            'columns' => array(
                0 => 'bill_type',
                1 => 'bill_id',
                2 => 'label_id',
            ),
            'prefix'  => 'unique',
        ),
        
        'in_label_id' => array(
            'columns' => array('label_id'),
        ),
    ),
    'comment' => '单据标记表',
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
