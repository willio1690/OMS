<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['bank_account']=array (
    'columns' =>
        array (
            'ba_id' =>
                array (
                    'type' => 'smallint(6)',
                    'required' => true,
                    'pkey' => true,
                    'editable' => false,
                    'in_list' => false,
                    'default_in_list' => true,
                    'extra' => 'auto_increment',
                    'comment' => '主键',
                ),
            'bank' =>
                array (
                    'type' => 'varchar(50)',
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'label' => '开户银行',
                    'default' => '',
                    'comment' => '银行',
                ),
            'account' =>
                array (
                    'type' => 'varchar(50)',
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'label' => '银行账号',
                    'default' => '',
                    'comment' => '银行账户',
                ),
            'holder' =>
                array (
                    'type' => 'varchar(50)',
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'label' => '开户人',
                    'default' => '',
                    'comment' => '持有者',
                ),
            'phone' =>
                array (
                    'type' => 'varchar(50)',
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'label' => '银行预留电话',
                    'default' => '',
                    'comment' => '联系电话',
                ),
        ),
    'index' =>
        array (
        ),
    'comment' => '银行账户信息',
    'engine' => 'innodb',
    'version' => '$Rev: 44513 $',
);
