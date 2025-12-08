<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['rule_shop']=array (
    'columns' =>
    array (
        'id' =>
        array (
            'type' => 'number',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'editable' => false,
        ),
        'rule_id' =>
        array (
            'type' => 'table:rule@logistics',
            'required' => true,
            'editable' => false,
        ),
        'shop_id' =>
        array (
            'type' => 'table:shop@ome',
            'required' => true,
            'editable' => false,
        ),
        'branch_id' =>
        array (
            'type' => 'table:branch@ome',
            'required' => true,
            'editable' => false,
        ),
    ),
    'index' =>
    array (
        'ind_rule_id_shop_id' =>
        array (
            'columns' =>
            array (
                0 => 'rule_id',
                1 => 'shop_id',
            ),
            'prefix' => 'unique',
        ),
    ),
    'comment' => '优选规则与店铺关系表',
    'engine' => 'innodb',
    'version' => '$Rev: 51996',
);