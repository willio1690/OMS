<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['refuse_reason']=array (
  'columns' => 
  array (
    'reason_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'reason_name' =>
    array (
      'type' => 'varchar(50)',
      'editable' => false,
      'default_in_list' => true,
      'in_list' => true,
      'label' => '原因描述',
      'is_title' => true,
      'searchtype' => 'has',
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
    'disabled' => 
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
    ),
  ),
  'comment' => '门店拒单原因表',
  'engine' => 'innodb',
);