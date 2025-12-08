<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['iostock_items']=array (
  'columns' =>
    array (
    'item_id' =>
    array (
        'type' => 'int unsigned',
        'required' => true,
        'pkey' => true,
        'extra' => 'auto_increment',
        'editable' => false,
        ),
    'cin_id' =>
    array (
        'type' => 'int unsigned',
        'required' => true,
        'default' => '0',
        'editable' => false,
    ),
    'invttype' =>
    array (
      'type' => 'number',
      'editable' => false,
      'label' => '出入库类型',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'cartoncount' =>
    array (
      'type' => 'number',
      'editable' => false,
      'label' => '箱数',
      'in_list' => true,
      'default_in_list' => true,
      'width' => 80,
    ),
    'plu' =>
    array (
      'type' => 'varchar(30)',
      'editable' => false,
      'label' => '货号',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'itemlotnum' =>
    array (
      'type' => 'varchar(30)',
      'editable' => false,
      'label' => '条码',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'outqty' =>
    array (
      'type' => 'varchar(10)',
      'editable' => false,
      'label' => '转移量',
      'in_list' => true,
      'default_in_list' => true,
      'width' => 80,
    ),
    'cartonnum' =>
    array (
      'type' => 'varchar(32)',
      'editable' => false,
      'label' => '纸箱装卸处理状态',
      'in_list' => true,
      'default_in_list' => true,
      'width' => 80,
    ),
  ),
  'comment' => '转仓单通知明细表',
  'engine' => 'innodb',
  'version' => '$Rev: 40912 $',
);
