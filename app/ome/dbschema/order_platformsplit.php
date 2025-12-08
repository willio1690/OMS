<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['order_platformsplit'] = array (
    'columns' => array(
        'id'           => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'editable' => false,
            'extra'    => 'auto_increment',
        ),
        'order_id' => array(
            'type' => 'table:orders@ome',
            'required' => true,
            'default'  => 0,
            'editable' => false,
            'label' => '订单ID',
            'width' => 120,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 1,
        ),
        'obj_id' => array (
            'type' => 'table:order_objects@ome',
            'required' => true,
            'default' => 0,
            'editable' => false,
            'label'  => '子单ID',
            'width' => 90,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 10,
        ),
        'split_oid' => array (
            'type' => 'varchar(30)',
            'editable' => false,
            'label'  => '拆分子单号',
            'width' => 120,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 12,
        ),
        'bn' => array (
            'type' => 'varchar(40)',
            'editable' => false,
            'label'  => '销售物料编码',
            'width' => 120,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 12,
        ),
        'num' => array (
            'type' => 'number',
            'editable' => false,
            'label'  => '拆分数量',
            'width' => 120,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 12,
        ),
        'create_time' => array(
            'type' => 'time',
            'label'  => '创建时间',
            'width' => 130,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 98,
        ),
    ), 
    'index' => array(
         
    ),
    'comment' => '订单平台拆分表',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);