<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['iso_items_detail'] = array(
    'columns' => array(
        'id'           => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
        ),
        'iso_items_id' => array(
            'type'     => 'table:iso_items@taoguaniostockorder',
            'required' => true,
            'default'  => 0,
            'editable' => false,
        ),
        'iso_id'       => array(
            'type'     => 'table:iso@taoguaniostockorder',
            'required' => true,
            'default'  => 0,
            'editable' => false,
        ),
        'product_id'   => array(
            'type'     => 'table:products@ome',
            'required' => true,
        ),
        'product_name' => array(
            'type'            => 'varchar(200)',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'label'           => '货品名称',
        ),
        'bn'           => array(
            'type'            => 'varchar(200)',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'label'           => '货号',
        ),
        'partcode'=> array(
          'type' => 'text',
          'label' => '备件条码',
          'in_list'         => true,
          'default_in_list' => true,
          'editable' => false,
        ),
        'price' =>
            array (
                'type' => 'money',
                'editable' => false,
            ),
        'nums'         => array(
            'type'            => 'number',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'label'           => '出入库数量',
        ),
        'batch_code'   => array(
            'type'            => 'varchar(100)',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'label'           => '批次号',
        ),
        'product_date' => array(
            'type'            => 'varchar(50)',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'label'           => '生产日期',
        ),
        'expire_date'  => array(
            'type'            => 'varchar(50)',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'label'           => '过期日期',
        ),
        'sn'           => array(
            'type'            => 'varchar(50)',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'label'           => '唯一码',
        ),
        'original_id'      => array(
            'type'    => 'int unsigned',
            'comment' => '原始明细ID',
        ),
        'up_time'      => array(
            'type'    => 'TIMESTAMP',
            'label'   => '更新时间',
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'width'   => 120,
            'in_list' => false,
            'order'   => 11,
        ),
        'at_time'      => array(
            'type'            => 'TIMESTAMP',
            'label'           => '创建时间',
            'default'         => 'CURRENT_TIMESTAMP',
            'width'           => 120,
            'in_list'         => false,
            'default_in_list' => false,
            'order'           => 11,
        ),
        'extendpro'   => array(
            'type'            => 'varchar(100)',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'label'           => '扩展信息',
        ),
        'box_no' => array(
            'type' => 'varchar(255)',
            'label' => '箱号',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'normal_num' => array (
            'type' => 'number',
            'label' => '良品数量',
            'default' => 0,
            'in_list' => true,
            'default_in_list' => true,
        ),
       'defective_num' => array (
            'type' => 'number',
            'label' => '不良品数量',
            'default' => 0,
            'in_list' => true,
            'default_in_list' => true,
        ),
    ),
    'index'   =>
        array(
            'ind_bn'      => array('columns' => array('bn')),
            'ind_at_time' => array('columns' => array('at_time')),
            'ind_up_time' => array('columns' => array('up_time')),
        ),
    'comment' => '出入库单信息明细表(批次维度)',
    'engine'  => 'innodb',
    'version' => '$Rev:  0',
);