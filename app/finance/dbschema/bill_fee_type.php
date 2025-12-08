<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['bill_fee_type']=array (
  'columns' => 
  array (
    'fee_type_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'fee_type' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'editable' => false,
      'label' => '科目类型',
      'default_in_list' => true,
      'in_list' => true,
    ),
    'outer_account_type' => array(
      'type' => 'number',
      'label' => '外部科目类型ID',
    ),
    'fee_type_code' => array(
      'type' => 'varchar(50)',
      'label' => '科目编码',
      'default_in_list' => true,
      'in_list' => true,
    ),
    'channel' => array(
      'type' => 'varchar(20)',
      'label' => '业务渠道',
      'default_in_list' => true,
      'in_list' => true,
    ),
    'related_order' => array(
      'type' => 'bool',
      'label' => '是否与订单相关',
      'default' => 'false',
      'default_in_list' => true,
      'in_list' => true, 
    ),
    'createtime' => array(
      'type' => 'time',
      'label' => '创建时间',
      'in_list' => true,
    ),
    'last_modified' => array(
      'type' => 'last_modify',
      'label' => '修改时间',
      'in_list' => true,
    ),
    'memo' => array(
      'type' => 'text',
      'label' => '备注',
      'in_list' => true,
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
    'comment' => '费用项类型',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);