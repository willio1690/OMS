<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['autobranchget']=array (
  'columns' => 
  array (
    'tid' => 
    array (
      'type' => 'number',
      'required' => true,
      'label' => '规则ID',
      'pkey' => true,
    ),
    'classify' => 
    array (
      'type' => 'varchar(255)',
      'required' => true,
      'label' => '分类：就近，成本',
      'pkey' => true,
    ),
   'weight'=>array(
      'type' => 'tinyint',
      'label' => '权重',
      'default'=>0,
    ),
  ),
  
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);