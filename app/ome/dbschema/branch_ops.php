<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['branch_ops']=array (
  'columns' => 
  array (
    'branch_id' => 
    array (
      'type' => 'table:branch@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'op_id' => 
    array (
      'type' => 'table:account@pam',
      'required' => true,
      'pkey' => true,
      'editable' => false, 
    ),
  ),
  'comment' => '仓库和管理员关联表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);