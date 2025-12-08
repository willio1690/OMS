<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['delivery_object_freeze']=array (
  'columns' => 
  array (
    'delivery_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'editable' => false,
    ),
    'sm_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'editable' => false,
    ),
    'number' => 
    array (
      'type' => 'number',
      'required' => true,
      'default' => 1,
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
  'comment' => '发货单销售物料的冻结数',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);