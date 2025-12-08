<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['ome_salestatistics']=array (
    'columns' => array (
        'record_id' => array (
            'type' => 'number',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'editable' => false,
        ),
        'shop_id' => array (
            'type' => 'varchar(32)',
            'editable' => false,
            'label' => '店铺ID',
        ),
        'shop_bn' => array(
            'type' => 'varchar(20)',
            'in_list' => true,
            'label' => '店铺编码',
            'default_in_list' => true,
            'order' => 10,
        ),
        'shop_name' => array(
            'type' => 'varchar(255)',
            'label' => '店铺名称',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 20,
        ),
        'shop_type' => array (
                'type' => 'varchar(50)',
                'label' => '店铺类型',
                'in_list' => true,
                'default_in_list' => true,
                'width' => '70',
                'order' => 30,
        ),
        'day' => array (
            'type' => 'time',
            'editable' => false,
            'label' => '发货时间',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 40,
        ),
        'order_num' => array (
            'type' => 'number',
            'editable' => false,
            'label' => '当日下单量',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 50,
        ),
        'order_amount' => array (
            'type' => 'money',
            'editable' => false,
            'label' => '当日下单金额',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 60,
        ),
        'delivery_num' => array (
            'type' => 'number',
        	'label' => '当日发货量',
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 70,
        ),
        'delivery_amount' => array (
            'type' => 'money',
        	'label' => '当日发货金额',
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 80,
        ),
        'delivery_return_num' => array (
            'type' => 'number',
            'label' => '发货退货量',
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 90,
        ),
        'delivery_return_amount' => array (
            'type' => 'money',
            'label' => '发货退货金额',
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 100,
        ),
        'return_num' => array (
            'type' => 'number',
            'label' => '当日退货量',
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'return_amount' => array (
            'type' => 'money',
            'label' => '当日退货金额',
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 120,
        ),
        'at_time'       => array(
            'type'            => 'TIMESTAMP',
            'label'           => '创建时间',
            'default'         => 'CURRENT_TIMESTAMP',
        ),
        'up_time'       => array(
            'type'            => 'TIMESTAMP',
            'label'           => '更新时间',
            'default'         => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'in_list' => true,
            'default_in_list' => true,
        ),
    ),
    'index' =>
      array (
        'ind_day' =>
        array (
            'columns' =>
            array (
              0 => 'day',
            ),
        ),
        'ind_shop_id'       => array('columns' => array('shop_id')),
        'ind_at_time'       => array('columns' => array('at_time')),
        'ind_up_time'       => array('columns' => array('up_time')),
      ),
    'comment' => '退货率统计',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);