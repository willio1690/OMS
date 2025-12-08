<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['bill_verification_error'] = array(
    'comment' => '核销误差表',
    'columns' => array(
        'id'          => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'editable' => false,
        ),
        'shop_id'     => array(
            'type'            => 'varchar(32)',
            'editable'        => false,
            'label'           => '所属店铺',
            'comment'         => '所属店铺',
            'width'           => 300,
            'in_list'         => true,
            'default_in_list' => false,
            'order'           => 1,
        ),
        'name'        => array(
            'type'            => 'varchar(255)',
            'label'           => '差异类型',
            'editable'        => false,
            'searchtype'      => 'has',
            'filtertype'      => 'normal',
            'filterdefault'   => true,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'money'       => array(
            'type'            => 'money',
            // 'required'        => true,
            'label'           => '误差范围',
            'comment'         => '误差范围',
            'width'           => 150,
            'editable'        => false,
            // 'in_list'         => true,
            // 'default_in_list' => true,
            'order'           => 11,
        ),
        'create_time' => array(
            'type'            => 'time',
            'label'           => '创建时间',
            'comment'         => '创建时间',
            'editable'        => false,
            'default_in_list' => true,
            'in_list'         => true,
            'width'           => 150,
            'order'           => 50,
        ),
        'memo'        => array(
            'type'     => 'longtext',
            'label'    => '备注日志',
            'editable' => false,
        ),
        'priority'    => array(
            'type'            => 'number',
            'label'           => '优先级',
            'default'         => 0,
            'editable'        => false,
            'default_in_list' => true,
            'in_list'         => true,
            'filterdefault'   => true,
            'filtertype'      => 'normal',
        ),
        'is_verify'   => array(
            'type'            => 'intbool',
            'label'           => '是否核销',
            'default'         => '0',
            'editable'        => false,
            'default_in_list' => true,
            'in_list'         => true,
            'filterdefault'   => true,
            'filtertype'      => 'normal',
        ),
        'verify_mode'   => array(
            'type'            => [
                '0' => '按订单总额核销',
                '1' => '订单收入和退款分开核销'
            ],
            'label'           => '核销方式',
            'default'         => '0',
            'editable'        => false,
            'default_in_list' => true,
            'in_list'         => true,
            'filterdefault'   => true,
            'filtertype'      => 'normal',
        ),
        'rule' => array (
          'type' => 'serialize',
          'label' => '规则内容',
        ),
    ),
    'index'   => array(
        'ind_shop_id'  => array(
            'columns' => array(
                'shop_id',
            ),
        ),
        'ind_priority' => array(
            'columns' => array(
                'priority',
            ),
        ),
    ),
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
