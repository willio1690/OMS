<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['wms_transferorder_items'] = array(
    'columns' => array(
        'id'        => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'label'    => 'ID',
            'width'    => 110,
            'hidden'   => true,
            'editable' => false,
        ),
        'wst_id'        => array(
            'type'            => 'table:wms_transferorder@console',
            'label'           => '第三方入库单',
        ),
        'product_bn'        => array(
            'type'            => 'varchar(255)',
            'default'         => '',
            'label'           => '货品编码',
        ),
        'normal_num'        => array(
            'type'            => 'varchar(255)',
            'default'         => 0,
            'label'           => '良品数',
        ),
        'defective_num'        => array(
            'type'            => 'varchar(255)',
            'default'         => 0,
            'label'           => '不良品数',
        ),
        'wms_item_id'        => array(
            'type'            => 'varchar(255)',
            'default'         => '',
            'label'           => '第三方货品编码',
        ),
        'in_count'        => array(
            'type'            => 'varchar(255)',
            'default'         => 0,
            'label'           => '入库数',
        ),
        'plan_count'        => array(
            'type'            => 'varchar(255)',
            'default'         => 0,
            'label'           => '计划数',
        ),
    ),
    'index'   => array(
        'idx_product_bn'     => array('columns' => array('product_bn')),
        'idx_wms_item_id'     => array('columns' => array('wms_item_id')),
    ),
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
    'comment' => '第三方转储单明细',
);
