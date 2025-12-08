<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 福袋组合数据结构
 *
 * @author wangbiao@shopex.cn
 * @version 2024.09.10
 */

$db['fukubukuro_combine'] = array(
    'columns' => array(
        'combine_id' => array(
            'pkey' => 'true',
            'type' => 'int unsigned',
            'extra' => 'auto_increment',
            'label' => '福袋组合ID',
            'required' => true,
            'order' => 1,
        ),
        'combine_bn' => array(
            'type' => 'varchar(32)',
            'label' => '福袋组合编码',
            'editable' => true,
            'searchtype' => 'nequal',
            'filtertype' => 'textarea',
            'filterdefault' => 'true',
            'in_list' => true,
            'default_in_list' => true,
            'width' => 180,
            'order' => 10,
        ),
        'combine_name' => array(
            'type' => 'varchar(50)',
            'label' => '福袋组合名称',
            'is_title' => true,
            'default_in_list' => true,
            'width' => 260,
            'searchtype' => 'has',
            'editable' => false,
            'filtertype' => 'normal',
            'filterdefault' => true,
            'in_list' => true,
            'order' => 12,
        ),
        'selected_number' => array(
            'type' => 'number',
            'editable' => false,
            'label' => '选中物料个数',
            'comment'  => '组合规则-选中基础物料个数',
            'default' => 1,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 20,
        ),
        'include_number' => array(
            'type' => 'number',
            'editable' => false,
            'label' => '包含件数',
            'comment'  => '组合规则-包含基础物料件数',
            'default' => 1,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 22,
        ),
        'selling_price' => array (
            'type' => 'money',
            'default' => '0.000',
            'label' => '福袋售价(未使用)',
            'in_list' => false,
            'default_in_list' => false,
            'width' => 110,
        ),
        'lowest_price' => array (
            'type' => 'money',
            'default' => '0.000',
            'label' => '最低价',
            'comment'=>'最低价',
            'in_list' => true,
            'default_in_list' => true,
            'width' => 110,
            'order' => 80,
        ),
        'highest_price' => array (
            'type' => 'money',
            'default' => '0.000',
            'label' => '最高价',
            'comment' => '最高价',
            'in_list' => true,
            'default_in_list' => true,
            'width' => 110,
            'order' => 82,
        ),
        'is_delete' => array(
            'type' => 'bool',
            'default' => 'false',
            'editable' => false,
            'label' => '删除状态',
            'comment' => '删除状态,可选值:true(是),false(否)',
            'in_list' => true,
            'default_in_list' => false,
            'order' => 90,
        ),
        'create_time' => array(
            'type' => 'time',
            'label' => '创建日期',
            'editable' => false,
            'filtertype' => 'time',
            'filterdefault' => true,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 98,
        ),
        'last_modified' => array(
            'label' => '最后更新时间',
            'type' => 'last_modify',
            'editable' => false,
            'filtertype' => 'time',
            'filterdefault' => true,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 99,
        ),
    ),
    'index' => array(
        'uni_combine_bn' => array(
            'columns' => array(
                0 => 'combine_bn',
            ),
            'prefix' => 'unique',
        ),
        'ind_combine_name' => array(
            'columns' => array(
                0 => 'combine_name',
            ),
        ),
        'ind_is_delete' => array(
            'columns' => array(
                0 => 'is_delete',
            ),
        ),
        'ind_create_time' => array(
            'columns' => array(
                0 => 'create_time',
            ),
        ),
        'ind_last_modified' => array(
            'columns' => array(
                0 => 'last_modified',
            ),
        ),
    ),
    'engine' => 'innodb',
    'version' => '$Rev: $',
    'comment' => '福袋组合表',
);
