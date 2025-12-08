<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['branch_type']=array (
  'columns' =>
  array (
    'type_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
 

    'type_code' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'in_list' => true,
      'default_in_list' => true,

      'editable' => false,
      'label' => '编码',
      'order' => '1',
    ),
    'type_name' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'in_list' => true,
      'default_in_list' => true,

      'editable' => false,
      'label' => '名称',
      'order' => '2',
    ),
    'type_desc' =>
    array (
      'type' => 'varchar(200)',
    
      'in_list' => true,
      'default_in_list' => true,
      'editable' => false,
      'label' => '描述',
      'order' => '3',
    ),
    'source' =>
    array (
      'type' => 'varchar(20)',
      'default' => 'local',
      'in_list' => true,
      'default_in_list' => true,
      'editable' => false,
      'label' => '来源',
      'order' => '4',
    ),
   'at_time'       => array(
      'type'    => 'TIMESTAMP',
      'label'   => '创建时间',
      'default' => 'CURRENT_TIMESTAMP',
      'width'   => 120,
      'in_list' => true,
      'order'   => 11,
    ),
    'up_time'       => array(
      'type'    => 'TIMESTAMP',
      'label'   => '更新时间',
      'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
      'width'   => 120,
      'in_list' => true,
      'order'   => 11,
    ),
  ),
  'index' => array (
    'ind_type_code' => array(
      'columns' => array(
        0 => 'type_code',
      ),
      
      'prefix' => 'unique',
    ),
     
  ),
  'comment' => '仓库类型表',
  'engine' => 'innodb',
  'version' => '$Rev: 41996 $',
);