<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['customcols']=array (
  'columns' => 
  array (
    'id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
      'label' => 'ID',
    ),
    'at_time'       => array(
        'type'            => 'TIMESTAMP',
        'label'           => '创建时间',
        'default_in_list' => true,
        'in_list'         => true,
        'filtertype'      => 'time',
        'filterdefault'   => true,
        'default'         => 'CURRENT_TIMESTAMP',
        'order' => 100,
    ),
    'up_time'       => array(
        'type'            => 'TIMESTAMP',
        'label'           => '更新时间',
        'default_in_list' => true,
        'in_list'         => true,
        'filtertype'      => 'time',
        'filterdefault'   => true,
        'default'         => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        'order' => 110,
    ),
    'tbl_name' => 
    array (
      'type' => 'varchar(50)',
      'required' => true,
      'label' => '表名',
    ),
    'p_order' => 
    array (
      'type' => 'number',
      'label' => '排序',
      'default' => 0,
    ),
    'disabled' => 
    array (
      'type' => 'bool',
      'label' => '无效状态',
      'default' => 'false',
    ),
    'col_name' => 
    array (
      'type' => 'varchar(50)',
      'label' => '字段名称',
      'required' => true,
    ),
    'col_key' => 
    array (
      'type' => 'varchar(50)',
      'label' => '字段键',
      'required' => true,
    ),
    'memo' => 
    array (
      'type' => 'varchar(50)',
      'label' => '字段描述',
    ),
  ),
  'index'   => array(
    'idx_at_time'       => array('columns' => array('at_time')),
    'idx_up_time'       => array('columns' => array('up_time')),
    'idx_col_key'   => array('columns' => array('col_key','tbl_name'), 'prefix' => 'UNIQUE'),
),
'engine'  => 'innodb',
'version' => '$Rev:  $',
  'comment' => '自定义字段',	
);