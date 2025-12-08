<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['order_items'] = array(
    'columns' => array(
        'item_id'            => array(
            'type'     => 'int unsigned',
            'extra'    => 'auto_increment',
            'pkey'     => true,
            'editable' => false,
            'label'    => '自增ID',
        ),
        'id'         => array(
            'type'  => 'table:order',
            'label' => '发票主表ID',
        ),
        'of_id'         => array(
            'type'  => 'table:order_front',
            'label' => '源数据主表ID',
        ),
        'of_item_id'    => array(
            'type'  => 'table:order_front_items',
            'label' => '源数据子表ID',
        ),
        'source_bn'     => array(
            'type'  => 'varchar(255)',
            'label' => '业务单号',
        ),
        'bn'            => array(
            'type'     => 'varchar(40)',
            'editable' => false,
            'label'    => '商品编码',
            'is_title' => true,
        ),
        'bm_id'         => array(
            'type'     => 'varchar(10)',
            'label'    => '商品id',
            'editable' => false,
        ),
        'item_type'     => array(
            'type'     => array(
                'sales' => '销售物料',
                'basic' => '基础物料',
                'ship'  => '运费',
            ),
            'default'  => 'sales',
            'label'    => '商品类型',
            'editable' => false,
        ),
        'item_name'     => array(
            'type'     => 'varchar(200)',
            'label'    => '明细名称',
            'editable' => false,
        ),
        'specification' => array(
            'type'     => 'varchar(200)',
            'label'    => '明细规格',
            'editable' => false,
        ),
        'unit'          => array(
            'type'     => 'varchar(200)',
            'label'    => '明细单位',
            'editable' => false,
        ),
        'amount'        => array(
            'type'     => 'money',
            'default'  => '0',
            'label'    => '开票金额',
            'editable' => false,
        ),
        'original_amount'        => array(
            'type'     => 'money',
            'default'  => '0',
            'label'    => '原始金额',
            'editable' => false,
        ),
        'tax_rate'      => array(
            'type'            => 'tinyint(2)',
            'default'         => '0',
            'label'           => '税率',
            'order'           => 52,
            'in_list'         => true,
            'default_in_list' => false,
        ),
        'cost_tax'          => array(
            'type'            => 'money',
            'default'         => '0',
            'label'           => '税金',
        ),
        'tax_code'      => array(
            'type'            => 'varchar(200)',
            'default'         => '0',
            'label'           => '开票编码',
            'order'           => 52,
            'in_list'         => true,
            'default_in_list' => false,
        ),
        'quantity'      => array(
            'type'     => 'number',
            'default'  => 0,
            'editable' => false,
        ),
        'is_delete'     => array(
            'type'     => 'bool',
            'default'  => 'false',
            'editable' => false,
        ),
        'item_is_make_invoice' => array(
            'type' => array(
                0 => '不可操作',
                1 => '可操作',
                2 => '待红冲',
            ),
            'default' => '0',
            'label'   => '开票操作',
        ),
        'inoperable_reason' => array(
            'type' => 'text',
            'default' => '0',
            'label'   => '不可操作原因',
        ),
        'original_id'      => array(
            'type'    => 'int unsigned',
            'comment' => '合票原发票id',
        ),
        'original_item_id'      => array(
            'type'    => 'int unsigned',
            'comment' => '合票原发票明细ID',
        ),
        'at_time'       => array(
            'type'            => 'TIMESTAMP',
            'label'           => '创建时间',
            'default'         => 'CURRENT_TIMESTAMP',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 1000,
        ),
        'up_time'       => array(
            'type'            => 'TIMESTAMP',
            'label'           => '更新时间',
            'default'         => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 1010,
        ),
    ),
    'index' => array(
        'idx_source_bn'            => array('columns' => array('source_bn')),
        'idx_bm_id'                => array('columns' => array('bm_id')),
        'idx_bn'                   => array('columns' => array('bn')),
        'idx_original_id'          => array('columns' => array('original_id')),
        'idx_original_item_id'     => array('columns' => array('original_item_id')),
        'idx_item_is_make_invoice' => array('columns' => array('item_is_make_invoice')),
        'idx_at_time'              => array('columns' => array('at_time')),
        'idx_up_time'              => array('columns' => array('up_time')),
    ),
    'engine'  => 'innodb',
    'commit'  => '发票明细表',
    'version' => 'Rev: 41996 $',
);