<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['delivery_order']=array (
  'columns' => 
  array (
    'order_id' => 
    array (
      'type' => 'table:orders@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'delivery_id' => 
    array (
      'type' => 'table:delivery@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
  ), 
  'index' =>
  array (
    'ind_delivery_id' =>
    array (
        'columns' =>
        array (
          0 => 'delivery_id',
        ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);