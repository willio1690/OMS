<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 福袋组合关联的基础物料数据结构
 *
 * @author wangbiao@shopex.cn
 * @version 2024.09.10
 */

$db['fukubukuro_combine_items'] = array(
    'columns' => array(
        'item_id' => array(
            'pkey' => 'true',
            'type' => 'int unsigned',
            'extra' => 'auto_increment',
            'label' => '福袋组合ID',
            'required' => true,
            'order' => 1,
        ),
        'combine_id' => array(
            'type' => 'int unsigned',
            'label' => '福袋组合ID',
            'required' => true,
            'order' => 10,
        ),
        'bm_id' => array(
            'type'     => 'int unsigned',
            'required' => true,
            'hidden'   => true,
            'editable' => false,
            'comment'  => '基础物料ID,关联material_basic_material.bm_id'
        ),
        'retail_price' => array (
            'type' => 'money',
            'default' => '0.000',
            'label' => '零售价',
            'comment'=>'基础物料零售价',
        ),
        'ratio' => array(
            'type'     => 'tinyint(3)',
            'editable' => false,
            'label'    => '选中比例(-1代表随机)',
            'default'  => 100,
            'hidden'   => true,
        ),
        'real_ratio' => array(
            'type'     => 'tinyint(3)',
            'editable' => false,
            'label'    => '选中实际比例',
            'default'  => 100,
            'hidden'   => true,
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
            'in_list' => true,
            'default_in_list' => true,
            'order' => 99,
        ),
    ),
    'index' => array(
        'ind_combine_bm_id' => array(
            'columns' => array(
                0 => 'combine_id',
                1 => 'bm_id',
            ),
            'prefix' => 'unique',
        ),
        'ind_bm_id' => array(
            'columns' => array(
                0 => 'bm_id',
            ),
        ),
    ),
    'engine' => 'innodb',
    'version' => '$Rev: $',
    'comment' => '福袋组合关联基础物料表',
);
