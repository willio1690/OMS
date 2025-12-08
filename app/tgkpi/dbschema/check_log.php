<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['check_log']=array (
  'columns' =>
  array (
  'l_id' =>
    array (
        'type' => 'number',
        'required' => true,
        'editable' => false,
        'pkey' => true,
        'label' => 'ID',
        'extra' => 'auto_increment',
    ),
    'delivery_id' =>
    array (
      'type' => 'table:delivery@ome',
      'required' => true,
      'editable' => false,
    ),
    'old_op_id' =>
    array (
      'type' => 'table:account@pam',
      'required' => true,
      'editable' => false,
    ),
    'new_op_id' =>
    array (
      'type' => 'table:account@pam',
      'required' => true,
      'editable' => false,
    ),
	'addtime' =>
    array (
        'type' => 'time',
        'required' => true,
        'editable' => false,
    ),
  ),
  'comment' => '拣货检验日志表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);