<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['bill_import_jzt'] = array(
    'columns' => array(
        'id' => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'editable' => false,
        ),
        'import_id' => array(
            'type' => 'int',
            'comment' => '导入id',
            'default_in_list' => true,
        ),
        'summary_id' => array(
            'type' => 'int',
            'comment' => '汇总id',
            'default_in_list' => true,
        ),
        'pay_serial_number' => array(
            'type' => 'varchar(255)',
            'label' => '流水单号',
            'comment' => '流水单号',
            'editable' => false,
            'searchtype' => 'nequal',
            'filtertype' => 'normal',
            'filterdefault' => true,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 10,
        ),
        'account' => array(
            'type' => 'varchar(50)',
            'label' => '账号',
            'comment' => '账号',
            'in_list' => true,
            'default_in_list' => true,
            'searchtype' => 'nequal',
            'filtertype' => 'normal',
            'filterdefault' => true,
            'order' => 15,
        ),
        'launchtime' => array(
            'type' => 'time',
            'label' => '投放日期',
            'comment' => '投放日期',
            'editable' => false,
            'filtertype'  => 'normal',
            'filterdefault' => false,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 20,
        ),
        'trade_type' => array(
            'type' => 'varchar(30)',
            'label' => '交易类型',
            'comment' => '交易类型',
            'in_list' => true,
            'default_in_list' => true,
            'searchtype' => 'nequal',
            'filtertype' => 'normal',
            'filterdefault' => true,
            'order' => 25,
        ),
        'plan_id' => array(
            'type' => 'varchar(100)',
            'label' => '计划ID',
            'comment' => '计划ID',
            'in_list' => true,
            'default_in_list' => true,
            'searchtype' => 'nequal',
            'filtertype' => 'normal',
            'filterdefault' => true,
            'order' => 30,
        ),
        'amount' => array(
            'type'  => 'money',
            'label' => '支出',
            'comment'  => '支出',
            'filtertype' => 'number',
            'filterdefault' => true,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 35,
        ),
        'at_time' => array (
            'type' => 'time',
            'label' => '创建时间',
            'width' => 130,
            'in_list' => true,
            'default_in_list' => true,
            'filtertype' => 'time',
            'filterdefault' => true,
            'order' => 98,
        ),
        'up_time' => array (
            'type' => 'time',
            'label' => '更新时间',
            'width' => 130,
            'in_list' => true,
            'default_in_list' => true,
            'filtertype' => 'time',
            'filterdefault' => true,
            'order' => 99,
        ),
        'op_id' => array (
            'type' => 'table:account@pam',
            'label'  => '确认人',
            'comment'  => '确认人',
            'editable' => false,
            'required' => true,
            'filterdefault' => true,
            'in_list' => true,
            'default_in_list' => false,
        ),
        'crc_unique' => array(
            'type' => 'varchar(80)',
            'label' => '唯一编号',
            'comment' => '唯一编号',
            'editable' => false,
            'default' => '0',
            'in_list' => true,
            'default_in_list' => false,
        ),
    ),
    'index'   => array(
        'ind_summary_id'  => array(
            'columns' => array(
                'summary_id',
            ),
        ),
        'ind_import_id'  => array(
            'columns' => array(
                'import_id',
            ),
        ),
        'ind_crc_unique' => array(
            'columns' => array(
                'crc_unique',
            ),
        ),
        'ind_pay_serial_number' => array(
            'columns' => array(
                'pay_serial_number',
            ),
        ),
        'ind_launchtime' => array(
            'columns' => array(
                'launchtime',
            ),
        ),
        'ind_amount' => array(
            'columns' => array(
                'amount',
            ),
        ),
    ),
    'comment' => '精准通账单明细',
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
