<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['fxw_orders']=array(
    'columns' => array(
        'order_id' => array(
            'type'     => 'table:orders@ome',
            'pkey' => true,
            'required' => true,
            'default'  => 0,
            'editable' => false,
            'comment'  => '分销王订单ID',
        ),
        'dealer_order_id' => array(
            'type'     => 'bigint(20)',
            'pkey' => true,
            'default'  => 0,
            'editable' => false,
            'comment'  => '分销王拉取的淘宝ID',
        ),
    ),
    'engine'  => 'innodb',
    'version' => '$Rev: 40912 $',
    'comment' => '分销王主表',
);