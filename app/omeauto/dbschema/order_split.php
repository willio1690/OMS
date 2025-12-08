<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['order_split'] = array(
    'columns' =>
        array(
            'sid' =>
                array(
                    'type' => 'number',
                    'required' => true,
                    'pkey' => true,
                    'extra' => 'auto_increment',
                    'editable' => false,
                ),
            'name' =>
                array(
                    'type' => 'varchar(200)',
                    'required' => true,
                    'editable' => false,
                    'is_title' => true,
                    'searchtype' => 'has',
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'in_list' => true,
                    'default_in_list' => true,
                    'width' => 130,
                    'label' => '规则名称',
                    'order' => 10
                ),
            'describe' =>
                array(
                    'type' => 'text',
                    'default' => '',
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => false,
                    'width' => 230,
                    'label' => '简述',
                    'order' => 20
                ),
            'split_type' =>
                array(
                    'type' => 'varchar(12)',
                    'default' => 0,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'width' => 200,
                    'label' => '拆单类型',
                    'order' => 30
                ),
            'split_config' =>
                array(
                    'type' => 'serialize',
                    'default' => '',
                    'editable' => false,
                    'in_list' => false,
                    'default_in_list' => false,
                    'width' => 200,
                    'label' => '拆单配置',
                ),
            'createtime' =>
                array(
                    'type' => 'time',
                    'label' => '创建时间',
                    'width' => 130,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => false,
                    'order' => 40
                ),
            'last_modified' =>
                array(
                    'label' => '最后修改时间',
                    'type' => 'last_modify',
                    'width' => 130,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'order' => 50
                ),
        ),
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);