<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 订单异常数据表
 *
 * @author wangbiao@shopex.cn
 * @version 2024.12.26
 */

$db['order_abnormal'] = array (
    'columns' => array(
        'aid' => array(
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'editable' => false,
            'extra' => 'auto_increment',
            'order' => 1,
        ),
        'order_id' => array(
            'type' => 'table:orders@ome',
            'required' => true,
            'default' => 0,
            'editable' => false,
            'label' => '订单ID',
            'width' => 120,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 10,
        ),
        'abnormal_type' => array (
            'type' => 'varchar(30)',
            'editable' => false,
            'label' => '异常类型',
            'width' => 130,
            'in_list' => true,
            'default_in_list' => true,
            'filtertype' => 'normal',
            'filterdefault' => true,
            'order' => 20,
        ),
        'abnormal_msg' => array (
            'type' => 'text',
            'editable' => false,
            'label' => '异常信息',
            'order' => 90,
        ),
        'at_time' => array(
            'type' => 'TIMESTAMP',
            'label' > '创建时间',
            'default' => 'CURRENT_TIMESTAMP',
            'width' => 120,
        ),
        'up_time' => array(
            'type' => 'TIMESTAMP',
            'label' => '更新时间',
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'width' => 120,
        ),
    ),
    'index' => array(
        'ind_abnormal_type' => array(
            'columns' => array(
                0 => 'abnormal_type',
            ),
        ),
        'ind_order_abnormal' => array(
            'columns' => array(
                0 => 'order_id',
                1 => 'abnormal_type',
            ),
        ),
        'ind_at_time' => array(
            'columns' => array(
                0 => 'at_time',
            ),
        ),
        'ind_up_time' => array(
            'columns' => array(
                0 => 'up_time',
            ),
        ),
    ),
    'comment' => '订单异常表',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);