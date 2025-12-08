<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['refund'] = array(
    'columns' => array(
        'id'         => array(
            'type'     => 'number',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'editable' => false,
        ),
        'refund_bn'  => array(
            'type'          => 'varchar(32)',
            'required'      => true,
            'label'         => '退款单ID',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => '2',
        ),
        'store_bn'         => array(
           'type'            => 'varchar(32)',
           'label'           => '下单门店编码',
           'in_list'         => true,
           'default_in_list' => true,
           'order'           => '3',
        ),
        'order_bn'         => array(
           'type'            => 'varchar(32)',
           'label'           => '交易订单号',
           'in_list'         => true,
           'default_in_list' => true,
           'order'           => '4',
        ),
        'oid'         => array(
           'type'            => 'varchar(32)',
           'label'           => '子订单号',
           'in_list'         => true,
           'default_in_list' => true,
           'order'           => '5',
        ),
      
        'money'         => array(
           'type'            => 'money',
           'label'           => '本次退款金额',
           'in_list'         => true,
           'default_in_list' => true,
           'order'           => '6',
        ),
        
        't_begin'         => array(
           'type'          => 'time',
           'label'           => '退款单创建时间',
           'in_list'         => true,
           'default_in_list' => true,
           'order'           => '7',
        ),
        
        'refund_type'         => array(
           'type'            => 'varchar(32)',
           'label'           => '退款单类型',
           'in_list'         => true,
           'default_in_list' => true,
        ),
        'at_time'       => array(
            'type'    => 'TIMESTAMP',
            'label'   => '创建时间',
            'default' => 'CURRENT_TIMESTAMP',
            'width'   => 120,
            'in_list'         => true,
            'default_in_list' => true,

        ),
        'up_time'       => array(
            'type'    => 'TIMESTAMP',
            'label'   => '更新时间',
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'width'   => 120,
            'in_list'         => true,
            'default_in_list' => true,

        ),
        'params'=>array(

            'type'     => 'longtext',
            'label'    => '原始请求参数',
        ),
    ),
    'index' => array(
        
        'ind_order_bn'          => array('columns' => array(0 => 'order_bn')),
        'ind_order_bn_refund'     => array('columns' => array(0 => 'refund_bn', 1 => 'store_bn'), 'prefix' => 'unique'),
        
    ),
    'comment' => '退款单申请',
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
