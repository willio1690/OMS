<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 销售物料基础物料关联数据结构
 *
 * @author maxiaochen@shopex.cn
 * @version 1.0
 */

$db['sales_basic_material'] = array(
    'columns' => array(
        'id'      => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'label'    => '自增ID',
        ),
        'sm_id'   => array(
            'type'     => 'int unsigned',
            'required' => true,
            'width'    => 110,
            'hidden'   => true,
            'editable' => false,
            'comment'  => '销售物料ID,关联material_sales_material.sm_id',
        ),
        'bm_id'   => array(
            'type'     => 'int unsigned',
            'required' => true,
            'width'    => 110,
            'hidden'   => true,
            'editable' => false,
            'comment'  => '基础物料ID,关联material_basic_material.bm_id',
        ),
        'number'  => array(
            'type'     => 'number',
            'editable' => false,
            'label'    => '数量',
            'default'  => 1,
            'hidden'   => true,
            'comment'  => '基础物料数量',
        ),
        'rate'    => array(
            'type'     => 'decimal(5,2)',
            'editable' => false,
            'label'    => '促销类基础物料价格贡献占比',
            'default'  => 100,
            'hidden'   => true,
        ),
        'at_time' => array(
            'type'            => 'TIMESTAMP',
            'label'           => '创建时间',
            'default'         => 'CURRENT_TIMESTAMP',
            'width'           => 150,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 80,
        ),
        'up_time' => array(
            'type'            => 'TIMESTAMP',
            'label'           => '更新时间',
            'default'         => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'width'           => 150,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 90,
        ),
    ),
    'index'   => array(
        'ind_sm_bm' => array('columns' => array('sm_id', 'bm_id')),
        'ind_bm'    => array('columns' => array('bm_id')),
    ),
    'comment' => '销售物料与基础物料关联表',
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
