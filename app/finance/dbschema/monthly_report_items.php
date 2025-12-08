<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['monthly_report_items'] = array(
    'columns' =>
    array(
        'id' =>
        array(
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'editable' => false,
        ),
        'monthly_id' =>
        array(
            'type' => 'table:monthly_report',
            'label' => '账单名称',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 10,
        ),
        'order_bn' => [
            'type' => 'varchar(255)',
            'label' => '订单号',
            'in_list' => true,
            'default_in_list' => true,
            'searchtype' => 'nequal',
            'filtertype' => 'normal',
            'filterdefault' => true,
            'order' => 20,
        ],
        'ship_time' => [
            'type' => 'time',
            'label' => '销售日期',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 30,
        ],
        'reship_time' => [
            'type' => 'time',
            'label' => '销退日期',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 35,
        ],
        'shishou_trade_time' => [
            'type' => 'time',
            'label' => '平台收入日期',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 30,
        ],
        'shitui_trade_time' => [
            'type' => 'time',
            'label' => '平台支出日期',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 40,
        ],
        'yingshou_money' => [
            'type' => 'money',
            'label' => '销售应收',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 50,
            'filtertype' => 'normal',
            'filterdefault' => true,
        ],
        'yingtui_money' => [
            'type' => 'money',
            'label' => '销售应退',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 60,
            'filtertype' => 'normal',
            'filterdefault' => true,
        ],
        'refund_only_money' => [
            'type' => 'money',
            'label' => '售后仅退款',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 60,
            'filtertype' => 'normal',
            'filterdefault' => true,
        ],
        'xiaotui_total' => [
            'type' => 'money',
            'label' => '销退合计',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 70,
            'filtertype' => 'normal',
            'filterdefault' => true,
        ],
        'shishou_money' => [
            'type' => 'money',
            'label' => '平台收入',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 80,
            'filtertype' => 'normal',
            'filterdefault' => true,
        ],
        'shitui_money' => [
            'type' => 'money',
            'label' => '平台支出',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 90,
            'filtertype' => 'normal',
            'filterdefault' => true,
        ],
        'shouzhi_total' => [
            'type' => 'money',
            'label' => '收支合计',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 100,
            'filtertype' => 'normal',
            'filterdefault' => true,
        ],
        'gap' => [
            'type' => 'decimal(20,3)',
            'label' => 'GAP',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 110,
            'filtertype' => 'normal',
            'filterdefault' => true,
        ],
        'gap_type' => [
            'type' => 'varchar(255)',
            'label' => '差异类型',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 120,
        ],
        'verification_status'   => array(
            'type'    => [
                '1' => '未核销',
                '2' => '已核销',
            ],
            'default' => '1',
            'label'   => '核销状态',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 130,
        ),
        'memo' => [
            'type' => 'text',
            'label' => '核销备注',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 140,
        ],
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
        'ind_order_bn_monthly_id' =>
        array(
            'columns' =>
            array(
                'order_bn','monthly_id'
            ),
            'prefix' => 'unique'
        ),
        'ind_verification_status' => [
            'columns' => [
                'verification_status'
            ]
        ],
        'ind_at_time' => ['columns' => ['at_time']],
        'ind_up_time' => ['columns' => ['up_time']],
    ),
    'comment' => '月结账单明细',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);