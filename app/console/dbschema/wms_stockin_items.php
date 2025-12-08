<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['wms_stockin_items'] = array(
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
        'wsi_id'        => array(
            'type'            => 'table:wms_stockin@console',
            'label'           => '第三方入库单',
        ),
        'tid'        => array(
            'type'            => 'varchar(255)',
            'default'         => '',
            'label'           => '单号',
        ),
        'oid'        => array(
            'type'            => 'varchar(255)',
            'default'         => '',
            'label'           => '子单号',
        ),
        'product_bn'        => array(
            'type'            => 'varchar(255)',
            'default'         => '',
            'label'           => '货品编码',
        ),
        'normal_num'        => array(
            'type'            => 'number',
            'default'         => 0,
            'label'           => '良品数',
        ),
        'defective_num'        => array(
            'type'            => 'number',
            'default'         => 0,
            'label'           => '不良品数',
        ),
        'sn_list'        => array(
            'type'            => 'text',
            'label'           => '唯一码',
        ),
        'batch'        => array(
            'type'            => 'text',
            'label'           => '批次号',
        ),
        'wms_item_id'        => array(
            'type'            => 'varchar(255)',
            'default'         => '',
            'label'           => '第三方货品编码',
        ),
        'at_time'       => array(
            'type'            => 'TIMESTAMP',
            'label'           => '创建时间',
            'default_in_list' => true,
            'in_list'         => true,
            'filtertype'      => 'time',
            'filterdefault'   => true,
            'default'         => 'CURRENT_TIMESTAMP',
            'order' => 100,
        ),
    ),
    'index'   => array(
        'idx_product_bn'     => array('columns' => array('product_bn')),
        'idx_wms_item_id'     => array('columns' => array('wms_item_id')),
    ),
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
    'comment' => '第三方入库单明细',
);
