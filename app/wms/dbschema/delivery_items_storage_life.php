<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['delivery_items_storage_life']=array (
  'columns' =>
  array (
    'itemsl_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'item_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'editable' => false,
    ),
    'delivery_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'editable' => false,
    ),
    'bm_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
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
    'expire_bn' =>
    array (
      'type' => 'varchar(200)',
      'editable' => false,
      'required'        => true,
    ),
    'number' =>
    array (
      'type' => 'number',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
    'verify' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
    ),
    'verify_num' =>
    array (
      'type' => 'number',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
  ),
  'comment' => '发货单明细预占保质期信息',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);