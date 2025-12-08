<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['series_endorse'] = array(
    'columns' => array(
        'en_id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'label' => '产品线授权到店ID',
        ),
        'series_id' => array(
            'type' => 'int unsigned',
            'label' => '产品线ID',
        ),
        'bs_id' => array(
            'type' => 'int unsigned',
            'label' => '经销商ID',
            'comment' => '一个经销店铺只能选一个经销商',
        ),
        'shop_id' => array(
            'type' => 'varchar(32)',
            'label' => '店铺ID',
        ),
        'sku_nums' => array(
            'type' => 'int unsigned',
            'label' => '产品数', // 店铺维度的数量
            'default' => '0',
        ),
        'at_time'     => array(
            'type'            => 'TIMESTAMP',
            'label'           => '创建时间',
            'default'         => 'CURRENT_TIMESTAMP',
            'width'           => 150,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 90,
        ),
        'up_time'     => array(
            'type'            => 'TIMESTAMP',
            'label'           => '更新时间',
            'default'         => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'width'           => 150,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 100,
        ),
    ),
    'index' => array (
        'ind_series_id' => array(
            'columns' => array(
                'series_id',
            ),
        ),
        'ind_shop_id' => array(
            'columns' => array(
                'shop_id',
            ),
        ),
        'ind_bs_id' => array(
            'columns' => array(
                'bs_id',
            ),
        ),
        'ind_at_time' => array(
            'columns' => array(
                'at_time',
            ),
        ),
        'ind_up_time' => array(
            'columns' => array(
                'up_time',
            ),
        ),
        
    ),
    'comment' => '产品线授权到店',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);