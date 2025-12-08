<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['branch_groups']=array (
  'columns' => 
  array (
    'branch_id' => 
    array (
      'type' => 'table:branch@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'group_id' => 
    array (
      'type' => 'table:groups@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
  ), 
  'comment' => '仓库和管理员组关系表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);