<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['pick_stockout_bill_item_boxs']=array (
    'columns' => array (
        'box_id' =>
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
        'stockout_item_id' =>
            array (
              'type' => 'number',
              'required' => true,
              'default' => 0,
              'label' => '出库单明细ID',
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
        'box_no' =>
            array (
                    'type' => 'varchar(50)',
                    'required' => false,
                    'label' => '箱号',
                    'width' => 100,
                    'editable' => false,
            ),
        'num' =>
            array (
                    'type' => 'number',
                    'label' => '数量',
                    'editable' => false,
                    'required' => false,
                    'default' => 0,
            ),
        'status' =>
            array (
                    'type' => 'tinyint(1)',
                    'label' => '出库状态',
                    'editable' => false,
                    'default' => 1,
            ),
    ),
    'index' => array (
        'ind_stockout_item_id' => array (
            'columns' => array (
                0 => 'stockout_item_id',
            ),
        ),
        'ind_stockout_id' => array (
            'columns' => array (
                0 => 'stockout_id',
            ),
        ),
        'ind_po_id' => array (
            'columns' => array (
                0 => 'po_id',
            ),
        ),
        'ind_bill_id' => array (
            'columns' => array (
                0 => 'bill_id',
            ),
        ),
    ),
    'comment' => '出库单明细装箱信息',
    'engine' => 'innodb',
    'version' => '$Rev: $',
);