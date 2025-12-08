<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['branch_area']=array (
  'columns' =>
  array (
    'branch_id' =>
    array (
      'type' => 'table:branch@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'region_id' =>
    array (
      'type' => 'table:regions@eccommon',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
  ),
  'comment' => '发货点和地区对应关系表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);