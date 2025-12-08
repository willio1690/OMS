<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['shop_onoffline']=array (
  'columns' => 
  array (
    'id' =>
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'on_id' =>
    array (
      'type' => 'table:shop@ome',
      'editable' => false,
      'default_in_list' => true,
      'in_list' => true,
      'label' => '网店ID',
    ),
    'off_id' =>
    array (
      'type' => 'table:shop@ome',
      'editable' => false,
      'default_in_list' => true,
      'in_list' => true,
      'label' => '门店ID',
    ),
  ),
  'engine' => 'innodb',
  'comment' => '网店和门店的关联表',
  'version' => '$Rev:  $',
);