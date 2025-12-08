<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['delivery_code']=array (
  'columns' =>
  array (
    'delivery_bn' =>
    array (
      'type' => 'varchar(32)',
      'comment' => '发货单单号',
      'required' => true,
      'editable' => false,
      'pkey' => true,
    ),
    'code' =>
    array (
      'type' => 'mediumint(6) unsigned',
      'comment' => '提货校验码',
      'required' => true,
      'editable' => false,
    ),
    'status' =>
    array (
      'type' => 'tinyint(1)',
      'required' => true,
      'editable' => false,
      'label' => '使用状态',
      'default' => 2,
    ),
    'create_time' =>
    array (
      'type' => 'time',
      'comment' => '生成时间',
      'required' => true,
      'editable' => false,
    ),
  ),
  'index' =>
  array (
    'ind_code' =>
    array (
      'columns' =>
      array (
        0 => 'code',
      ),
      'prefix' => 'unique',
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
  'comment' => '门店提货校验码关联表'
);