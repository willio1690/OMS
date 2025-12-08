<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['groups']=array (
  'columns' => 
  array (
    'group_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'name' => 
    array (
      'type' => 'varchar(100)',
      'required' => true,
      'is_title' => true,
      'label' => '名称',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
    'config' => 
    array (
      'type' => 'text',
      'editable' => false,
    ),
    'description' =>
    array (
      'type' => 'text',
      'editable' => false,
    ),
    'g_type' =>
    array (
      'type' => 'varchar(20)',
      'editable' => false,
      'required' => true,
      'default' => 'confirm',
      'label' => '所属版块',
    ),
    'org_id' =>
    array (
      'type' => 'table:operation_organization@ome',
      'label' => '运营组织',
      'editable' => false,
      'width' => 60,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'disabled' =>
    array (
      'type' => 'bool',
      'default' => 'false',
      'editable' => false,
      'required' => true,
    ),
  ),
  'comment' => '管理员组',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);