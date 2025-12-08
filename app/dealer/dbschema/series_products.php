<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['series_products'] = array(
    'columns' => array(
        'sp_id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'label' => '产品线绑物料ID',
        ),
        'series_id' => array(
            'type' => 'int unsigned',
            'label' => '产品线ID',
        ),
        'bm_id' => array(
            'type' => 'int unsigned',
            'label' => '物料ID',
        ),
        'op_name' => array(
            'type' => 'varchar(32)',
            'label' => '创建人',
            'in_list' => true,
        ),
        'at_time'  => array(
            'type'            => 'TIMESTAMP',
            'label'           => '创建时间',
            'default'         => 'CURRENT_TIMESTAMP',
            'width'           => 150,
            // 'in_list'         => true,
            // 'default_in_list' => true,
            'order'           => 40,
        ),
        'up_time'  => array(
            'type'            => 'TIMESTAMP',
            'label'           => '更新时间',
            'default'         => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'width'           => 150,
            // 'in_list'         => true,
            // 'default_in_list' => true,
            'order'           => 50,
        ),
    ),
    'index' => array (
        'ind_series_id' => array(
            'columns' => array(
                'series_id',
            ),
        ),
        'ind_bm_id' => array(
            'columns' => array(
                'bm_id',
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
    'comment' => '产品线绑物料',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);