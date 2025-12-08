<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['farm_order']=array (
    'columns' =>
        array (
            'order_id' =>
                array (
                    'type' => 'table:orders@ome',
                    'required' => true,
                    'pkey' => true,
                    'editable' => false,
                ),
            'farm_id' =>
                array (
                    'type' => 'table:farm@brush',
                    'required' => true,
                    'pkey' => true,
                    'editable' => false,
                ),
        ),
    'comment' => '刷单与订单对应表',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);