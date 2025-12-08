<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 销售商品数据结构
 *
 * @author maxiaochen@shopex.cn
 * @version 1.0
 */

$db['sales_material'] = array(
    'columns' => array(
        'sm_id'               => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'label'    => 'ID',
            'width'    => 110,
            'hidden'   => true,
            'editable' => false,
            'comment'  => '自增主键ID',
        ),
        'sales_material_bn'   => array(
            'type'            => 'varchar(200)',
            'label'           => '销售商品编码',
            'width'           => 120,
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'searchtype'      => 'nequal',
            'filtertype'      => 'textarea',
            'filterdefault'   => true,
            'order'           => 20,
        ),
        'sales_material_name' => array(
            'type'            => 'varchar(200)',
            'label'           => '销售商品名称',
            'is_title'        => true,
            'default_in_list' => true,
            'width'           => 260,
            'searchtype'      => 'has',
            'editable'        => false,
            'filtertype'      => 'normal',
            'filterdefault'   => true,
            'in_list'         => true,
            'order'           => 10,
        ),
        'sales_material_type' => array(
            'type'            => 'tinyint(1)',
            'label'           => '销售商品类型',
            'width'           => 100,
            'editable'        => false,
            'default'         => 1,
            'in_list'         => true,
            'default_in_list' => true,
            'comment'         => '销售商品类型,可选值:1(普通),2(组合)',
            'order'           => 25,
        ),
        'is_bind'             => array(
            'type'    => 'tinyint(1)',
            'default' => 2,
            'label'   => '是否绑定基础商品',
            'comment' => '是否绑定基础商品,可选值:1(是), 0(否)',
        ),
        'shop_id'             => array(
            'type'            => 'varchar(32)',
            'label'           => '所属店铺',
            'in_list'         => true,
            'default_in_list' => true,
            'searchtype'      => 'nequal',
            'default'         => '_ALL_',
            'order'           => 40,
            'width'           => 100,
        ),
        'cos_id'              => array(
            'type'     => 'table:cos@organization',
            'label'    => '组织架构ID',
            'editable' => false,
        ),
        'op_name'             => array(
            'type'            => 'varchar(32)',
            'label'           => '创建人',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 110,
            'width'           => 80,
        ),
        'at_time'             => array(
            'type'            => 'TIMESTAMP',
            'label'           => '创建时间',
            'default'         => 'CURRENT_TIMESTAMP',
            'width'           => 150,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 80,
        ),
        'up_time'             => array(
            'type'            => 'TIMESTAMP',
            'label'           => '更新时间',
            'default'         => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'width'           => 150,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 90,
        ),
    ),
    'comment' => '销售商品表',
    'index'   => array(
        'ind_sales_material_shop' => array(
            'columns' => array(
                0 => 'shop_id',
                1 => 'sales_material_bn',
            ),
            'prefix'  => 'UNIQUE',
        ),
        'ind_sales_material_bn'   => array(
            'columns' => array(
                0 => 'sales_material_bn',
            ),
        ),
        'ind_shop_id'             => array(
            'columns' => array(
                0 => 'shop_id',
            ),
        ),
        'ind_is_bind'             => array(
            'columns' => array(
                0 => 'is_bind',
            ),
        ),
        'ind_sales_material_type' => array(
            'columns' => array(
                0 => 'sales_material_type',
            ),
        ),
    ),
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
