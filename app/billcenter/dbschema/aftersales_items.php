<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['aftersales_items'] = [
    'columns' => [
        'id' => [
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
        ],
        'aftersale_id' => [
            'type' => 'int unsigned',
            'required' => true,
            'label' => 'VOP售后单ID',
        ],
        'material_bn' => [
            'type' => 'varchar(200)',
            'required' => true,
            'label' => '基础物料编码',
            'width' => 100,
            'in_list' => true,
        ],
        'barcode' => [
            'type' => 'varchar(200)',
            'label' => '基础物料条码',
            'width' => 100,
            'in_list' => true,
        ],
        'material_name' => [
            'type' => 'varchar(200)',
            'label' => '基础物料名称',
            'width' => 100,
            'in_list' => true,
        ],
        'bm_id' => [
            'type' => 'int unsigned',
            'label' => '基础物料ID',
            'width' => 100,
        ],
        'nums' =>[
            'type' => 'number',
            'label' => '发货数量',
            'default' => 0,
            'in_list' => true,
        ],
        'price'             => array(
            'type'    => 'money',
            'default' => 0,
            'label'   => '零售单价',
            'in_list' => true,
        ),
        'amount'             => array(
            'type'    => 'money',
            'default' => 0,
            'label'   => '零售小计',
            'in_list' => true,
        ),
        'sale_price' => array(
            'type'    => 'money',
            'default' => 0,
            'label'   => '售后小计',
            'in_list' => true,
        ),
//        'pmt_price' => array(
//                'type' => 'money',
//                'default' => 0,
//                'editable' => false,
//                'comment' => '优惠小计',
//                'in_list'         => false,
//                'default_in_list' => false,
//            ),
        'settlement_amount' => array(
            'type'    => 'money',
            'default' => '0',
            'label'   => '结算小计',
            'in_list' => true,
        ),
        'box_no' => array(
            'type' => 'varchar(255)',
            'label' => '退供箱号',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'at_time'           => [
            'type'    => 'TIMESTAMP',
            'label'   => '创建时间',
            'default' => 'CURRENT_TIMESTAMP',
            'width'   => 120,
//            'in_list' => true,
        ],
        'up_time'           => [
            'type'    => 'TIMESTAMP',
            'label'   => '更新时间',
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'width'   => 120,
//            'in_list' => true,
        ],
        'original_item_id' => [
            'type' => 'int unsigned',
            'label' => '原始明细ID',
        ],
    ],
    'index' => [
        'ind_at_time' => ['columns' => ['at_time']],
        'ind_up_time' => ['columns' => ['up_time']],
        'ind_original_item_id' => ['columns' => ['original_item_id']],
        'ind_aftersale_id' => ['columns' => ['aftersale_id']],
        'ind_material_bn' => ['columns' => ['material_bn']],
        'ind_bm_id' => ['columns' => ['bm_id']],
    ],
    'comment' => '唯品会售后单明细',
    'engine' => 'innodb',
    'version' => '$Rev: $',
];
