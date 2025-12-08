<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['rule_items']=array (
    'columns' =>
    array (
        'item_id' =>
        array (
          'type' => 'int unsigned',
          'required' => true,
          'pkey' => true,
          'editable' => false,
          'extra' => 'auto_increment',
        ),
        'obj_id' =>
        array (
        'type' => 'table:rule_obj@logistics',
        'required' => true,
        'default' => 0,
        'editable' => false,
        'comment' => '规则对象ID',
        ),
        'min_weight' =>
        array (
            'type' => 'number',
            'editable' => false,
            'comment' => '最小重量',
        ),
        'max_weight' =>
        array(
            'type' => 'int',
            'editable' => false,
            'comment' => '最大重量',
        ),
        'corp_id'=>array (
            'type' => 'int',
            'editable' => false,
            'comment' => '物流公司ID',
        ),
        'second_corp_id'=>array (
            'type' => 'int',
            'editable' => false,
        ),
    ),
    'comment' => '规则明细',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);