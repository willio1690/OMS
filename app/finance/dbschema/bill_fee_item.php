<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['bill_fee_item']=array (
  'columns' => 
  array (
    'fee_item_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'fee_type_id' => 
    array (
      'type' => 'int',
      'required' => true,
      'editable' => false,
      'comment' => '费用类ID',
    ),
    'fee_item' =>
    array (
      'type' => 'varchar(255)',
      'required' => true,
      'editable' => false,
	  'comment' => '费用项',      'default_in_list' => true,
      'in_list' => true,
      'filtertype' => true,
      'is_title' => true,
    ),
    'inlay' =>
    array (
      'type' => 'bool',
      'default'=>'false',
      'comment'=>'是否为内置（内置不可删除）',
      'required' => true,
      'editable' => false,
    ),
    'channel' => array(
      'type' => 'varchar(20)',
      'label' => '业务渠道',
      'in_list' => true,
      'filterdefault' => true,
    ),
    'related_order' => array(
      'type' => 'bool',
      'label' => '是否与订单相关',
      'default' => 'false',
      'in_list' => true,
      'filtertype' => true,
    ),
    'createtime' => array(
      'type' => 'time',
      'label' => '创建时间',
      'default_in_list' => true,
      'in_list' => true,
    ),
    'last_modified' => array(
      'type' => 'last_modify',
      'label' => '最后更新时间',
      'in_list' => true,
    ),
    'memo' => array(
      'type' => 'text',
      'label' => '备注',
    ),
    'fee_item_code' => array(
      'type' => 'varchar(50)',
      'label' => '科目编号',
      'default_in_list' => true,
      'in_list' => true,
      'filtertype' => true,
    ),
    'outer_account_id' => array(
      'type' => 'varchar(32)',
      'label' => '外部科目ID',
    ),
    'delete' =>
    array (
      'type' => 'bool',
      'default'=>'false',
      'comment'=>'是否删除',
      'required' => true,
      'editable' => false,
    ),

  ), 
  'index' => array(
    'idx_code_channel' => array('columns' => array('fee_item_code','channel'),'prefix' => 'unique'),
    'idx_outer_account_id' => array('columns' => array('outer_account_id')),
  ),
    'comment' => '费用项',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);