<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['bill_base'] = array(
    'columns' => array(
        'shop_id'     => array(
            'type'     => 'varchar(32)',
            'editable' => false,
            'comment'  => '店铺ID',
        ),
        'unique_id'   => array(
            'type'    => 'varchar(32)',
            'comment' => '单据唯一值',
        ),
        'content'     => array(
            'type'    => 'longtext',
            'comment' => '原始数据',
        ),
        'create_time' => array(
            'type'            => 'time',
            'comment'         => '单据生成时间(插入数据库的时间)',
            'editable'        => false,
            'in_list'         => false,
            'default_in_list' => false,
        ),
    ),
    'index'   => array(
        'unique_key'      => array(
            'columns' => array(
                0 => 'shop_id',
                1 => 'unique_id',
            ),
            'prefix'  => 'unique',
        ),
        'create_time_key' => array(
            'columns' => array(
                'create_time',
            ),
        ),
        'idx_unique_id'   => array(
            'columns' => array(
                'unique_id',
            ),
        ),
    ),
    'comment' => '流水原始数据表',
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
