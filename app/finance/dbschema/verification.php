<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['verification']=array (
  'columns' => 
  array (
    'log_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'log_bn' => 
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'label' => '核销流水号',
      'searchtype' => 'nequal',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'editable' => false,
    ),
    'op_time' => 
    array (
      'type' => 'time',
      'required' => true,
      'label' => '核销日期',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'filtertype' => 'time',
      'filterdefault' => true,
    ),
    'op_name' =>
    array (
      'type' => 'varchar(200)',
      'label' => '核销人',
      'comment' => '核销人',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'editable' => false,
    ),
    'type' => 
    array (
      'type' => 'tinyint',
      'required' => true,
      'label' => '核销类型',
      'editable' => false,
      'comment'=>'应收互冲核销(0)，应收实收核销(1)',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
    'money' =>
    array (
      'type' => 'money',
      'label' => '核销金额',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'content' => 
    array (
      'type' => 'serialize',
      'editable' => false,
      'label'=>'核销摘要',
      'comment'=>'核销摘要',
    ),
  ),
  'index'=>array(
    'ind_type' =>
    array (
        'columns' =>
        array (
          0 => 'type',
        ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);