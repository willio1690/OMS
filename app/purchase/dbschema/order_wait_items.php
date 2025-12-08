<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['order_wait_items'] = array(
    'columns' => array(
        'owi_id' =>
            array(
                'type' => 'number',
                'required' => true,
                'pkey' => true,
                'extra' => 'auto_increment',
                'editable' => false,
                'order' => 1,
            ),
        'ow_id' =>
            array(
                'type' => 'table:order_wait@purchase',
                'required' => true,
                'editable' => false,
                'order' => 2,
            ),
        'product_id' =>
            array(
                'type' => 'table:products@ome',
                'default' => 0,
                'label' => '货品ID',
                'order' => 3,
            ),
        'bn' =>
            array(
                'type' => 'varchar(32)',
                'default' => '',
                'label' => '货号',
                'order' => 3,
            ),
        'barcode' =>
            array(
                'type' => 'varchar(32)',
                'default' => '',
                'label' => '条形码',
                'order' => 3,
            ),
        'product_name' =>
            array(
                'type' => 'varchar(32)',
                'default' => '',
                'label' => '货品名',
                'order' => 3,
            ),
        'brand_name' =>
            array(
                'type' => 'varchar(32)',
                'default' => '',
                'label' => '品牌',
                'order' => 3,
            ),
        'size' =>
            array(
                'type' => 'varchar(32)',
                'default' => '',
                'label' => '尺寸',
                'order' => 3,
            ),
        'quantity' =>
            array(
                'type' => 'varchar(32)',
                'default' => '',
                'label' => '数量',
                'order' => 3,
            ),
        'po_no' =>
            array(
                'type' => 'varchar(32)',
                'default' => '',
                'label' => '采购订单号',
                'order' => 3,
            ),
        'cooperation_no' =>
            array(
                'type' => 'varchar(32)',
                'default' => '',
                'label' => '常态合作编码',
                'order' => 3,
            ),
    ),
    'comment' => '待寻仓订单明细',
    'engine' => 'innodb',
    'version' => '$Rev: $',
);