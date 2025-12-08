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
     'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'comment' => '订单ID',
    ),
    'delivery_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'comment' => '发货单ID',
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
  'comment' => '归档发货单和订单多对多的关系表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);