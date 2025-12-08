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
      'comment' => '订单ID,关联ome_orders.order_id',
    ),
    'delivery_id' => 
    array (
      'type' => 'table:delivery@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'comment' => '发货单ID,关联ome_delivery.delivery_id',
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
    'ind_order_id' =>
    array (
        'columns' =>
        array (
          0 => 'order_id',
        ),
    ),
  ),
  'comment' => '发货单和订单多对多关系表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);