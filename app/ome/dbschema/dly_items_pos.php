<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['dly_items_pos']=array (
  'columns' => 
  array (
    'item_id' => 
    array (
      'type' => 'table:delivery_items@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'pos_id' =>
    array (
      'type' => 'table:branch_pos@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'num' =>
    array (
      'type' => 'number',
      'editable' => false,
      'default' => 0,
      'required' => true,
    ),
  ), 
  'comment' => '发货单明细中的货品的货位关系表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);