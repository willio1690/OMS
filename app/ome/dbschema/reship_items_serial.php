<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['reship_items_serial']=array (
  'columns' =>
  array (
    'item_serial_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'reship_id' =>
    array (
      'type' => 'table:reship@ome',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
    'product_id' =>
    array (
      'type' => 'table:products@ome',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
    'bn' =>
    array (
      'type' => 'varchar(30)',
      'editable' => false,
      'is_title' => true,
    ),
    'product_name' =>
    array (
      'type' => 'varchar(200)',
      'editable' => false,
    ),
    'serial_number' => 
    array (
      'type' => 'varchar(30)',
      'required' => true,
      'editable' => false,
      'label' => '唯一码',
    ),
  ),
  'index' =>
  array (
    'idx_c_serial_number' =>
    array (
      'columns' =>
      array (
        0 => 'serial_number',
      ),
    ),
  ),
  'comment' => '退货单货品唯一码表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);