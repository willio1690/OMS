<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

// ====================================================
// == 此表已废弃 请转用sdb_ome_bill_label表 2023.09.26 ==
// == 此表已废弃 请转用sdb_ome_bill_label表 2023.09.26 ==
// == 此表已废弃 请转用sdb_ome_bill_label表 2023.09.26 ==
// ====================================================
$db['order_label'] = array (
    'columns' => array(
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
        'label_id' => array (
            'type' => 'int(10)',
            'required' => true,
            'default' => 0,
            'editable' => false,
            'label'  => '标记ID',
            'width' => 90,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 10,
        ),
        'label_name' => array (
            'type' => 'varchar(30)',
            'editable' => false,
            'label'  => '标记名称',
            'width' => 120,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 12,
        ),
        'label_desc' => array (
            'type' => 'varchar(150)',
            'editable' => false,
            'label'  => '标记描述',
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
        'in_label_id' => array(
            'columns'=> array('label_id')
        ),
    ),
    'comment' => '订单标记表',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);