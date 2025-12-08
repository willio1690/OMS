<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['order_labels'] = array(
    'columns' => array(
        'label_id' => array(
            'type' => 'number',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'editable' => false,
            'order' => 1,
        ),
        'label_name' => array(
            'type' => 'varchar(30)',
            'required' => true,
            'editable' => false,
            'searchtype' => 'has',
            'filtertype' => 'normal',
            'filterdefault' => true,
            'in_list' => true,
            'default_in_list' => true,
            'width' => 130,
            'label' => '标签名称',
            'order' => 12,
        ),
        'label_code' => array(
            'type' => 'varchar(30)',
            'required' => true,
            'editable' => false,
            'searchtype' => 'has',
            'filtertype' => 'normal',
            'filterdefault' => true,
            'in_list' => true,
            'default_in_list' => true,
            'width' => 120,
            'label' => '标签代码',
            'order' => 10,
        ),
        'label_color' => array(
            'type' => 'varchar(30)',
            'required' => true,
            'editable' => false,
            'searchtype' => 'has',
            'filtertype' => 'normal',
            'filterdefault' => true,
            'in_list' => false,
            'default_in_list' => false,
            'width' => 120,
            'label' => '标签颜色',
            'order' => 15,
        ),
        'source' => array(
            'type' => 'varchar(30)',
            'searchtype' => 'has',
            'filtertype' => 'normal',
            'filterdefault' => true,
            'in_list' => true,
            'default_in_list' => true,
            'width' => 130,
            'label' => '标签来源',
            'order' => 20,
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
        'last_modified' => array(
            'type' => 'time',
            'label' => '最后更新时间',
            'width' => 130,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 99,
        ),
    ),
    'index' => array(
        'in_label_code' => array(
            'columns'=> array('label_code')
        ),
        'in_label_name' => array(
            'columns'=> array('label_name')
        ),
    ),
    'comment' => '标签表',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);