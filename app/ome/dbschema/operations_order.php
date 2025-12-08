<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['operations_order']=array (
  'columns' => 
  array (
    'operation_id' => 
    array (
      'type' => 'int unsigned',
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'log_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'editable' => false,
    ),
	'order_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'editable' => false,
    ),
	'order_detail' =>
	array(
	  'type' => 'longtext',
      'required' => true,
      'editable' => false,
	),
  ),
  'index' => array(
    'idx_log_id' => array('columns' => array('log_id')),
    'idx_order_id' => array('columns' => array('order_id')),
  ),
  'comment' => '订单操作扩展表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
  'charset' => 'utf8mb4',
);