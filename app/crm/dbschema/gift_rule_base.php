<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

// 赠品发放规则
$db['gift_rule_base']=array (
    'columns' => 
    array (
        'id' =>
        array(
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'editable' => false,
            'extra' => 'auto_increment',
            'order' => 10
        ),
        'rule_bn' =>
        array (
            'type' => 'varchar(32)',
            'required' => true,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'label' => '规则编号',
            'searchtype' => 'has',
            'filtertype' => 'normal',
            'order' => 20
        ),
        'title' =>
        array (
            'type' => 'varchar(64)',
            'required' => true,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'label' => '规则名称',
            'searchtype' => 'has',
            'filtertype' => 'normal',
            'order' => 30
        ),
        'gift_list' =>
        array (
            'type' => 'text',
            'required' => false,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'label' => '赠品列表',
            'order' => 35
        ),
        'create_time' => 
        array (
            'type' => 'time',
            'required' => true,
            'label' => '创建时间',
            'filtertype' => 'time',
            'filterdefault' => true,
            'in_list' => true,
            'default_in_list' => true,
            'width' => 150,
            'order' => 60
        ),
        'modified_time' => 
        array (
            'type' => 'time',
            'required' => true,
            'label' => '修改时间',
            'filtertype' => 'time',
            'filterdefault' => true,
            'in_list' => true,
            'default_in_list' => true,
            'width' => 150,
            'order' => 65
        ),
        'filter_arr' => 
        array(
            'type' => 'longtext',
            'default' => 1,
            'label'=>'促销条件',
            'default_in_list' => false,
            'in_list' => false,
            'order' => 150,
        ),
        'disabled' =>
        array (
                'type' => 'bool',
                'default' => 'false',
                'comment' => '是否已删除',
                'editable' => false,
        ),
    ),
    'index' =>
        array(
            'ind_rule_bn' =>
                array (
                    'columns' =>
                        array (
                            'rule_bn'
                        ),
                    'prefix' => 'unique'
                ),
        ),
    'engine' => 'innodb',
    'version' => '$Rev:  $'
);