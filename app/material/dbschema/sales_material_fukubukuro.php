<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['sales_material_fukubukuro'] = array(
    'columns' => array(
        'fd_id' => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'label'    => '主键ID',
            'hidden'   => true,
            'editable' => false,
        ),
        'sm_id' => array(
            'type'     => 'int unsigned',
            'required' => true,
            'width'    => 110,
            'hidden'   => true,
            'editable' => false,
            'comment'  => '销售物料ID,'
        ),
        'combine_id' => array(
            'type'     => 'int unsigned',
            'required' => true,
            'width'    => 110,
            'hidden'   => true,
            'editable' => false,
            'comment'  => '福袋组合ID'
        ),
        'number' => array(
            'type'     => 'int unsigned',
            'editable' => false,
            'label'    => '数量',
            'default'  => 1,
            'hidden'   => true,
            'comment'  => '福袋组合数量'
        ),
        'rate_price' => array (
            'type' => 'money',
            'default' => '0.000',
            'label' => '组合贡献价',
            'comment' => '组合贡献价',
            'in_list' => true,
            'default_in_list' => true,
            'width' => 110,
            'order' => 80,
        ),
        'rate' => array(
            'type'     => 'decimal(5,2)',
            'editable' => false,
            'label'    => '销售价贡献占比',
            'default'  => 100,
            'hidden'   => true,
        ),
    ),
    'index' => array(
        'ind_combine_bm_id' => array(
            'columns' => array(
                0 => 'sm_id',
                1 => 'combine_id',
            ),
            'prefix' => 'unique',
        ),
        'ind_combine_id' => array(
            'columns' => array(
                0 => 'combine_id',
            ),
        ),
    ),
    'comment' => '销售物料与福袋组合关联表, 用于存储两者关系, 支持一对多',
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
