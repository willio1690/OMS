<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['reason']=array (
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
    'reason_memo' =>
    array (
      'type' => 'text',
      'editable' => false,
      'default_in_list' => true,
      'in_list' => true,
      'label' => '原因内容',
    ),
    'disabled' =>
    array (
      'type' => 'bool',
      'default' => 'false',
      'editable' => false,
    ),
  ),
  'comment' => '拣货校验原因表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);