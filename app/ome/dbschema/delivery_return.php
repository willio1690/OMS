<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['delivery_return']=array (
  'columns' => 
  array (
    'return_id' => 
    array (
      'type' => 'table:return_product@ome',
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
  'comment' => '发货单和售后服务关系表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);