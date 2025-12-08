<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['iostock_type']=array (
  'columns' =>
  array (
    'type_id' =>
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
   ),
    'type_name' =>
    array (
     'is_title' => true,
      'editable' => false,
      'type' => 'varchar(32)',
      'required' => true,
      'comment' => '类型名称',
      'label'=>'类型名称',
      'is_title' => true,
       'in_list' => true,
      'default_in_list' => true,
    ),
  ),
  'comment' => '出入库类型',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);