<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['iso_items'] = array(
    'columns' => array(
        'item_id'           => array(
            'type'     => 'number',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'editable' => false,
        ),
        'iso_id'            => array(
            'type' => 'table:iso@pos',
        ),
        'product_id'        => array(
            'type'            => 'int unsigned',
            'in_list'         => true,
            'default_in_list' => true,
            'default'         => 0,
        ),
        'goods_bn'          => array(
            'type' => 'varchar(50)',
        ),
        'goods_name'        => array(
            'type'            => 'varchar(32)',
            'label'           => '商品名称',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'price'             => array(
            'type'            => 'varchar(32)',
            'label'           => '商品金额',
            'in_list'         => false,
            'default_in_list' => false,
        ),
        'specifications'    => array(
            'type'            => 'varchar(32)',
            'label'           => '商品规格',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'goods_attr'        => array(
            'type'            => 'varchar(32)',
            'label'           => '商品属性',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'storage_code'      => array(
            'type'            => 'varchar(32)',
            'label'           => '库位',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'basic_calc_unit'   => array(
            'type'            => 'varchar(32)',
            'label'           => '基本计量单位',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'plan_qty'          => array(
            'type'            => 'varchar(32)',
            'label'           => '应收数量(基本单位)',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'actual_qty'        => array(
            'type'            => 'varchar(32)',
            'label'           => '实收数量(基本单位)',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'assist_calc_unit'  => array(
            'type'            => 'varchar(32)',
            'label'           => '辅助计量单位',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'plan_qty_assist'   => array(
            'type'            => 'varchar(32)',
            'label'           => '应收数量(辅助单位)',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'actual_qty_assist' => array(
            'type'            => 'varchar(32)',
            'label'           => '实收数量(辅助单位)',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'batch_code'        => array(
            'type'            => 'varchar(32)',
            'label'           => '批次号',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => '60',
        ),
        'product_date'      => array(
            'type'            => 'time',
            'label'           => '生成日期',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => '70',
        ),
        'expire_date'       => array(
            'type'            => 'time',
            'label'           => '有效期至',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => '80',
        ),
        'item_remark'       => array(
            'type'            => 'text',
            'label'           => '明细行备注',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'at_time'           => array(
            'type'    => 'TIMESTAMP',
            'label'   => '创建时间',
            'default' => 'CURRENT_TIMESTAMP',
            'width'   => 120,
            'in_list' => false,
            'order'   => 90,
        ),
        'up_time'           => array(
            'type'    => 'TIMESTAMP',
            'label'   => '更新时间',
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'width'   => 120,
            'in_list' => false,
            'order'   => 100,
        ),
    ),
    'index'   => array(
        'ind_goods_bn'           => array('columns' => array('goods_bn')),
        'ind_id_product_storage' => array(
            'columns' => array('iso_id', 'product_id', 'storage_code'),
            'prefix'  => 'unique'
        ),
    ),
    'comment' => 'POS其他出入库明细',
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
