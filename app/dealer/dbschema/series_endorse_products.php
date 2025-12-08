<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['series_endorse_products'] = array(
    'columns' => array(
        'sep_id'           => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'label'    => '产品授权到店ID',
        ),
        'en_id'            => array(
            'type'  => 'int unsigned',
            'label' => '产品线授权到店ID',
        ),
        'series_id'        => array(
            'type'  => 'int unsigned',
            'label' => '产品线ID',
        ),
        'shop_id'          => array(
            'type'  => 'varchar(32)',
            'label' => '店铺ID',
        ),
        'bm_id'            => array(
            'type'  => 'int unsigned',
            'label' => '物料ID',
        ),
        'is_shopyjdf_type' => array(
            'type'    => array(
                1 => '自发货',
                2 => '代发货',
            ),
            'default' => '1',
            'label'   => '发货方式',
        ),
        'sale_status'    =>  array(
            'type'    => array(
                0 => '否',
                1 => '是',
            ),
            'default' => '1',
            'label'   => '可售状态',
        ),
        'op_name'          => array(
            'type'            => 'varchar(32)',
            'editable'        => false,
            'label'           => '创建者', // 取账号名
            'width'           => 90,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 60,
        ),
        'from_time'        => array(
            'type'          => 'time',
            'label'         => '开始时间',
            'comment'       => '自发改成代发需要设置时间范围，代发变更自发设置开始时间',
            'width'         => 130,
            'editable'      => false,
            'filtertype'    => 'time',
            'filterdefault' => true,
            'in_list'       => true,
        ),
        'end_time'         => array(
            'type'          => 'time',
            'label'         => '结束时间',
            'comment'       => '自发改成代发需要设置时间范围，代发变更自发设置开始时间',
            'width'         => 130,
            'editable'      => false,
            'filtertype'    => 'time',
            'filterdefault' => true,
            'in_list'       => true,
        ),
        'at_time'          => array(
            'type'            => 'TIMESTAMP',
            'label'           => '创建时间',
            'default'         => 'CURRENT_TIMESTAMP',
            'width'           => 150,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 40,
        ),
        'up_time'          => array(
            'type'            => 'TIMESTAMP',
            'label'           => '更新时间',
            'default'         => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'width'           => 150,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 50,
        ),
    ),
    'index'   => array(
        'ind_shop_bm_id' => array(
            'columns' => array(
                'shop_id',
                'bm_id',
            ),
        ),
        'ind_en_id'      => array(
            'columns' => array(
                'en_id',
            ),
        ),
        'ind_series_id'  => array(
            'columns' => array(
                'series_id',
            ),
        ),
        'ind_shop_id'    => array(
            'columns' => array(
                'shop_id',
            ),
        ),
        'ind_bm_id'      => array(
            'columns' => array(
                'bm_id',
            ),
        ),
        'ind_is_shopyjdf_type'  => array(
            'columns' => array(
                'is_shopyjdf_type',
            ),
        ),
        'ind_sale_status'   => array(
            'columns' => array(
                'sale_status',
            ),
        ),
        'ind_at_time'    => array(
            'columns' => array(
                'at_time',
            ),
        ),
        'ind_up_time'    => array(
            'columns' => array(
                'up_time',
            ),
        ),

    ),
    'comment' => '产品授权到店',
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
