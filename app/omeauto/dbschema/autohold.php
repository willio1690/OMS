<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['autohold']=array (
  'columns' => 
  array (
    'tid' => 
    array (
      'type' => 'number',
      'required' => true,
      'label' => '规则ID',
      'pkey' => true,
    ),
    'hold' => 
    array (
      'type' => array('all'=>'全部','part'=>'部分'),
      'required' => true,
      'label' => 'hold单',
      'default' => 'all',
    ),
   'hours'=>array(
      'type' => 'int',
      'label' => 'hold单小时数',
      'default'=>0,
    ),
  ),
  
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);