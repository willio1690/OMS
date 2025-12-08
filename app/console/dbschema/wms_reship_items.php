<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['wms_reship_items'] = array(
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
        'wr_id'        => array(
            'type'            => 'table:wms_reship@console',
            'label'           => '第三方退货单',
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
        'extend_props'        => array(
            'type'            => 'text',
            'label'           => '扩展字段',
        ),
    ),
    'index'   => array(
        'idx_product_bn'     => array('columns' => array('product_bn')),
        'idx_wms_item_id'     => array('columns' => array('wms_item_id')),
    ),
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
    'comment' => '第三方退货单明细',
);
