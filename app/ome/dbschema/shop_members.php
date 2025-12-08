<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['shop_members']=array (
  'columns' => 
  array (
    'shop_id' => 
    array (
      'type' => 'table:shop@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'shop_member_id' => 
    array (
      'type' => 'varchar(255)',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'member_id' => 
    array (
      'type' => 'table:members@ome',
      'required' => true,
      'editable' => false,
    ),
  ),
  'comment' => '前端店铺会员和ome会员对应关系表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);
