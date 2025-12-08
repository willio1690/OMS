<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


$db['task']=array (
  'columns' => 
  array (
    'task' => array('type'=>'varchar(100)','pkey'=>true),
    'minute' => array('type'=>'time'),
    'hour' => array('type'=>'time'),
    'day' => array('type'=>'time'),
    'week' => array('type'=>'time'),
    'month' => array('type'=>'time'),
  ),
  'comment' => '计划任务表',
  'version' => '$Rev: 41137 $',
  'ignore_cache' => true,
);
