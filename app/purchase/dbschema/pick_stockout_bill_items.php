<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['pick_stockout_bill_items']=array (
    'columns' => array (
        'stockout_item_id' =>
            array (
              'type' => 'int unsigned',
              'required' => true,
              'pkey' => true,
              'extra' => 'auto_increment',
              'editable' => false,
        ),
        'stockout_id' =>
            array (
              'type' => 'number',
              'required' => true,
              'default' => 0,
              'label' => '出库单ID',
              'editable' => false,
        ),
        'po_id' =>
            array (
                    'type' => 'number',
                    'required' => true,
                    'default' => 0,
                    'label' => '采购单ID',
                    'editable' => false,
            ),
        'bill_id' =>
            array (
                    'type' => 'number',
                    'required' => true,
                    'default' => 0,
                    'label' => '拣货单ID',
                    'editable' => false,
            ),
        'bn' =>
            array (
                    'type' => 'varchar(30)',
                    'required' => false,
                    'label' => '货号',
                    'width' => 100,
                    'editable' => false,
            ),
        'product_name' =>
            array (
                    'type' => 'varchar(80)',
                    'label' => '商品名称',
                    'width' => 130,
                    'editable' => false,
            ),
        'barcode' =>
            array (
                    'type' => 'varchar(80)',
                    'required' => false,
                    'label' => '条码',
                    'width' => 100,
                    'editable' => false,
            ),
        'size' =>
            array (
                    'type' => 'varchar(32)',
                    'label' => '尺寸',
                    'width' => 80,
                    'editable' => false,
                    'in_list' => true,
            ),
        'num' =>
            array (
                    'type' => 'number',
                    'label' => '申请数量',
                    'editable' => false,
                    'required' => false,
                    'default' => 0,
            ),
        'item_num' =>
            array (
                    'type' => 'number',
                    'label' => '应出库数量',
                    'editable' => false,
                    'required' => false,
                    'default' => 0,
            ),
        'actual_num' =>
            array (
                    'type' => 'number',
                    'label' => '实际出库数量',
                    'editable' => false,
                    'required' => false,
                    'default' => 0,
            ),
        'price' =>
            array (
                    'type' => 'money',
                    'label' => '供货价(不含税)',
                    'width' => 60,
                    'required' => true,
                    'editable' => false,
            ),
        'market_price' =>
            array (
                    'type' => 'money',
                    'label' => '供货价(含税)',
                    'width' => 60,
                    'required' => true,
                    'editable' => false,
            ),
        'is_del' =>
            array (
                    'type' => 'bool',
                    'default' => 'false',
                    'editable' => false,
                    'label' => '是否删除状态',
                    'order'=>99,
            ),
        'product_id' =>
            array (
                'type' => 'int unsigned',
                'editable' => false,
            ),
            'po_bn' =>
            array(
                    'type' => 'varchar(32)',
                    'label' => '采购单号',
            ),
            'pick_no' =>
            array(
                    'type' => 'varchar(32)',
                    'label' => '拣货单号',
            ),
    ),
    'index' => array (
        'ind_stockout_id'   => array ('columns' =>array ('stockout_id',)),
        'ind_product_id'    => array ('columns' =>array ('product_id',)),
        'ind_po_id'         => array ('columns' =>array ('po_id',)),
        'ind_bill_id'       => array ('columns' =>array ('bill_id',)),
    ),
    'comment' => '出库单明细',
    'engine' => 'innodb',
    'version' => '$Rev: $',
);