<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['region_rule']=array (
  'columns' =>
  array (
  'id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
    ),

    'item_id' =>
    array (
      'type' => 'int',
      'required' => true,
      'default' => 0,
      'editable' => false,
      'comment' => '项目ID',
    ),

    'region_id' =>
    array (
      'type' => 'int',
      'required' => true,
      'default' => 0,
      'editable' => false,
      'comment' => '区域ID',
    ),
    'region_grade' =>
        array(
            'type' => 'number',
            'editable' => false,
            'comment' => '区域级别',
        ),

 'obj_id' =>
    array (
      'type' => 'int',
      'required' => true,
      'default' => 0,
      'editable' => false,
      'comment' => '对象ID',
    ),
),
 'index' =>array (
 'ind_region_id' =>
    array (
      'columns' =>
      array (
        0 => 'region_id',
      ),
    ),

 ),
  'comment' => '区域规则',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);