<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 人工库存预占组表
 * by wangjianjun 20180207
 */

$db['basic_material_stock_artificial_freeze_group']=array (
  'columns' =>
  array (
    'group_id' => array(
        'type'     => 'int unsigned',
        'required' => true,
        'pkey'     => true,
        'extra'    => 'auto_increment',
        'editable' => false,
    ),
    'group_name' => array(
        'type' => 'varchar(50)',
        'comment' => '组名',
        'label' => '组名',
        'editable' => false,
        'required' => true,
    ),
  ),
    'index' => array (
        'ind_group_name' =>
        array (
            'columns' =>
            array (
                0 => 'group_name',
            ),
        ),
    ),
  'comment' => '人工库存预占组表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);
