<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['aftersale'] = array(
    'columns' => array(
        'id'         => array(
            'type'     => 'number',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'editable' => false,
        ),
   
        'return_bn'=>array(
            'type'            => 'varchar(32)',
            'in_list'         => true,
            'default_in_list' => true,
            'label'   => '售后申请单ID',
            'order'           => '2',
        ),
        'order_bn'=>array(
            'type'            => 'varchar(32)',
            'in_list'         => true,
            'default_in_list' => true,
            'label'           => '订单号',
            'order'           => '3',
        ),
        'store_bn'=>array(
            'type'            => 'varchar(32)',
            'in_list'         => true,
            'default_in_list' => true,
            'label'   => '下单门店编码',
            'order'           => '4',
        ),
        'refund_fee'=>array(
            'type'    => 'money',
            'label'   => '退款金额',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => '5',
        ),
        'status'=>array(
            'type'            => 'varchar(32)',
            'in_list'         => true,
            'default_in_list' => false,
            'label'=>'状态',
        ),
        'at_time'       => array(
            'type'    => 'TIMESTAMP',
            'label'   => '创建时间',
            'default' => 'CURRENT_TIMESTAMP',
            'width'   => 120,
            'in_list' => false,
            'order'   => 11,
        ),
        'up_time'       => array(
            'type'    => 'TIMESTAMP',
            'label'   => '更新时间',
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'width'   => 120,
            'in_list' => false,
            'order'   => 11,
        ),
        'params'=>array(

            'type'     => 'longtext',
            'label'    => '原始请求参数',
        ),
    ),
    'index' => array(
       
        'ind_order_bn'          => array('columns' => array(0 => 'order_bn')),
        'ind_return_bn_store'   => array('columns' => array(0 => 'return_bn', 1 => 'store_bn'), 'prefix' => 'unique'),
    ),
    'comment' => '售后单申请原始数据',
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
