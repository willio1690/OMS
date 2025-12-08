<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


$db['cache_expires']=array (
  'columns' => 
  array (
    'type' => array(
        'type' => 'varchar(20)',
        'pkey' => true,
        'required' => true,
    ),
    'name' => array(
        'type'=>'varchar(255)',
        'pkey' => true,
        'required'=>true,
    ),
    'expire' => array(
        'type'=>'time',
        'required' => true,
    ),
    'app' => array(
        'type'=>'varchar(50)',
        'required'=>true,
    ),
  ),
  'index' => 
  array (
    'ind_name' => 
    array (
      'columns' => 
      array (
        0 => 'name',
      ),
    ),
  ), 
  'comment' => '缓存过期表', 
  'engine' => 'innodb',
  'version' => '$Rev: 41137 $',
  'ignore_cache' => true,
);
