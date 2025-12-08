<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['verification_items']=array (
  'columns' => 
  array (
    'item_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'log_id' => 
    array (
      'type' => 'int',
      'required' => true,
      'editable' => false,
    ),
    'bill_id' => 
    array (
      'type' => 'int',
      'required' => true,
    ),
    'bill_bn' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'comment' => '单据编号'
    ),
    'type' => 
    array (
      'type' => 'int',
      'required' => true,
      'comment' => '单据号类型 实收单据（0） 应收单据（1）',
    ),
    'money' =>
    array (
      'type' => 'money',
      'comment' => '核销金额'
    ),
    'trade_time' => 
    array (
      'type' => 'time',
      'required'=>true,
      'editable' => false,
      'comment'=>'账单完成时间',
    ),
  ),
  'index'=>array(
    'ind_log_id' =>
    array (
        'columns' =>
        array (
          0 => 'log_id',
        ),
    ),
    'ind_bill_id' =>
    array (
        'columns' =>
        array (
          0 => 'bill_id',
        ),
    ),
    'ind_type' =>
    array (
        'columns' =>
        array (
          0 => 'type',
        ),
    ),
    'ind_money' =>
    array (
        'columns' =>
        array (
          0 => 'money',
        ),
    ),
    'ind_trade_time' =>
    array (
        'columns' =>
        array (
          0 => 'trade_time',
        ),
    ),
  ), 
  'comment' => '核销日志明细',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);