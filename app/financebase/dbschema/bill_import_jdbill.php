<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['bill_import_jdbill'] = array(
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
        'member_id' => array(
            'type' => 'varchar(30)',
            'label' => '商户号',
            'comment' => '商户号',
            'editable' => false,
            'searchtype' => 'nequal',
            'filtertype' => 'normal',
            'filterdefault' => true,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 10,
        ),
        'account_no' => array(
            'type' => 'varchar(50)',
            'label' => '账户代码',
            'comment' => '账户代码',
            'in_list' => true,
            'default_in_list' => true,
            'searchtype' => 'nequal',
            'filtertype' => 'normal',
            'filterdefault' => true,
            'order' => 15,
        ),
        'account_name' => array(
            'type' => 'varchar(100)',
            'label' => '账户名称',
            'comment' => '账户名称',
            'in_list' => true,
            'default_in_list' => true,
            'searchtype' => 'nequal',
            'filtertype' => 'normal',
            'filterdefault' => true,
            'order' => 20,
        ),
        'trade_time' => array(
            'type' => 'time',
            'label' => '交易日期',
            'comment' => '交易日期',
            'editable' => false,
            'filtertype'  => 'normal',
            'filterdefault' => false,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 25,
        ),
        'trade_no' => array(
            'type' => 'varchar(100)',
            'label' => '商户订单号',
            'comment' => '商户订单号',
            'in_list' => true,
            'default_in_list' => true,
            'searchtype' => 'nequal',
            'filtertype' => 'normal',
            'filterdefault' => true,
            'order' => 30,
        ),
        'account_balance' => array(
            'type'  => 'money',
            'label' => '账户余额',
            'comment'  => '账户余额',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 40,
        ),
        'income_fee' => array(
            'type'  => 'money',
            'label' => '收入金额',
            'comment'  => '收入金额',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 45,
        ),
        'outgo_fee' => array(
            'type'  => 'money',
            'label' => '支出金额',
            'comment'  => '支出金额',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 50,
        ),
        'bill_time' => array(
            'type' => 'time',
            'label' => '账单日期',
            'comment' => '账单日期',
            'editable' => false,
            'filtertype'  => 'normal',
            'filterdefault' => false,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 55,
        ),
        'remark' => array(
            'type' => 'varchar(255)',
            'label' => '账单备注',
            'comment' => '账单备注',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 90,
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
        'ind_member_id' => array(
            'columns' => array(
                'member_id',
            ),
        ),
        'ind_account_no' => array(
            'columns' => array(
                'account_no',
            ),
        ),
        'ind_trade_no' => array(
            'columns' => array(
                'trade_no',
            ),
        ),
        'ind_trade_time' => array(
            'columns' => array(
                'trade_time',
            ),
        ),
        'ind_bill_time' => array(
            'columns' => array(
                'bill_time',
            ),
        ),
    ),
    'comment' => '京东钱包流水',
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
