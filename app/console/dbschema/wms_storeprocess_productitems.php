<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['wms_storeprocess_productitems'] = array( 
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
        'wsp_id'        => array(
            'type'            => 'table:wms_storeprocess@console',
            'label'           => '第三方加工单',
        ),
        'item_code'        => array(
            'type'            => 'varchar(255)',
            'default'         => '',
            'label'           => 'erp系统商品编码',
        ),
        'item_id'        => array(
            'type'            => 'varchar(255)',
            'default'         => '',
            'label'           => '仓储系统商品ID',
        ),
        'inventory_type'        => array(
            'type'            => 'varchar(25)',
            'default'         => '',
            'label'           => '库存类型',
        ),
        'quantity'        => array(
            'type'            => 'number',
            'default'         => 0,
            'label'           => '数量',
        ),
        'product_date'        => array(
            'type'            => 'varchar(25)',
            'default'         => '',
            'label'           => '商品生产日期',
        ),
        'expire_date'        => array(
            'type'            => 'varchar(25)',
            'default'         => '',
            'label'           => '商品过期日期',
        ),
        'produce_code'        => array(
            'type'            => 'varchar(255)',
            'default'         => '',
            'label'           => '生产批号',
        ),
        'batch_code'        => array(
            'type'            => 'varchar(25)',
            'default'         => '',
            'label'           => '批次编码',
        ),
        'remark'        => array(
            'type'            => 'text',
            'default'         => '',
            'label'           => '备注',
        ),
    ),
    'index'   => array(
        'idx_item_code'     => array('columns' => array('item_code')),
        'idx_item_id'     => array('columns' => array('item_id')),
    ),
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
    'comment' => '第三方加工单商品',
);
