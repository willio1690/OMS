<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['delivery_items_storagelife']=array (
  'columns' =>
  array (
    'item_storagelife_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'delivery_id' =>
    array (
      'type' => 'table:delivery@ome',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
    'bm_id' =>
    array (
      'type' => 'table:basic_material@material',
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
    'expire_bn' => 
    array (
      'type' => 'varchar(200)',
      'required' => true,
      'editable' => false,
      'label' => '保质期批次号',
    ),
    'number' => 
    array (
      'type' => 'number',
      'required' => true,
      'default' => 0,
      'editable' => false,
      'label' => '数量',
    ),
    'status' => 
    array (
      'type' => 'tinyint(1)',
      'required' => true,
      'default' => 0,
      'editable' => false,
      'label' => '状态',
    ),
  ),
  'index' =>
  array (
    'idx_c_expire_bn' =>
    array (
      'columns' =>
      array (
        0 => 'expire_bn',
      ),
    ),
  ),
  'comment' => '发货单货品保质期批次表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);