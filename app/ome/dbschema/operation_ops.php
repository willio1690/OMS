<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['operation_ops']=array (
  'columns' => 
  array (
    'org_id' => 
    array (
      'type' => 'table:operation_organization@ome',
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
  'comment' => app::get('ome')->_('运营组织管理员'),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);