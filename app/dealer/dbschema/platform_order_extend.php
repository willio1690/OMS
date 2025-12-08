<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['platform_order_extend'] = array(
    'columns' => array(
        'plat_order_id' => array(
            'pkey' => 'true',
            'type' => 'int unsigned',
            'label' => '订单ID',
            'required' => true,
        ),
        'plat_order_bn' => array(
            'type' => 'varchar(32)',
            'label' => '订单号',
            'editable' => true,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'extend_info' => array(
            'type' => 'longtext',
            'label' => '平台订单原始信息',
            'editable' => false,
            'in_list' => false,
            'default_in_list' => false,
        ),
        'create_time' => array(
            'type' => 'time',
            'label' => '创建时间',
            'editable' => false,
            'in_list' => true,
            'default_in_list' => false,
        ),
        'last_modified' => array(
            'type' => 'time',
            'label' => '最后修改时间',
            'editable' => false,
            'in_list' => true,
            'default_in_list' => false,
        ),
    ),
    'index' => array(
        'uni_plat_order_id' => array(
            'columns' => array(
                0 => 'plat_order_id',
            ),
            'prefix' => 'unique',
        ),
        'uni_plat_order_bn' => array(
            'columns' => array(
                0 => 'plat_order_bn',
            ),
            'prefix' => 'unique',
        ),
        'ind_create_time' => array(
            'columns' => array(
                0 => 'create_time',
            ),
        ),
    ),
    'engine' => 'innodb',
    'version' => '$Rev: $',
    'comment' => '平台订单信息表',
);