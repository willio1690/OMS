<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['autobranch']=array (
  'columns' => 
  array (
    'tid' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      ),
    'bid' => 
    array (
      'type' => 'mediumint',
      'required' => true,
      'pkey' => true,
    ),
   'weight'=>array(
   'type' => 'tinyint',
   'default'=>0,

   ),
   'is_default' =>
    array (
      'type' => 'intbool',
      'default' => '0',
      'required' => true,
      'editable' => false,
    ),
  ),
  'comment' => '自动审单仓库规则',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);