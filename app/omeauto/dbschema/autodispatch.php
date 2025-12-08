<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['autodispatch']=array (
  'columns' => 
  array (
    'oid' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'name' => 
    array (
      'type' => 'varchar(200)',
      'required' => true,
      'editable' => false,
      'is_title' => true,
      'searchtype' => 'has',
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
      'width' => 130,
      'label' => '规则名称',
    ),
    'config' =>
    array (
      'type' => 'serialize',
      'editable' => false,
    ),
    'memo' =>
    array (
      'type' => 'text',
      'editable' => false,
    ),
    'disabled' => 
    array (
      'type' => 'bool',
      'required' => true,
      'editable' => false,
      'default' => 'false',
    ),
    'defaulted' => 
    array (
      'type' => 'bool',
      'required' => true,
      'editable' => false,
      'default' => 'false',
    ),
    'group_id' => 
    array (
      'type' => 'table:groups@ome',
      'editable' => false,
      'label' => '订单确认组',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'op_id' => 
    array (
      'type' => 'table:account@pam',
      'editable' => false,
      'label' => '订单确认员',
      'in_list' => true,
      'default_in_list' => true,
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
  ),
  'comment' => '自动分配规则', 
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);