<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['bill_confirm']=array (
  'comment' => '账单确认表',
  'columns' => 
  array (
    'confirm_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'trade_no' => 
    array (
      'type' => 'varchar(32)',
      'label' => '交易凭据号',
      'width' => 130,
      'order' => '2',
      'searchtype' => 'nequal',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'order_bn' => 
    array (
      'type' => 'varchar(100)',
      'required' => true,
      'label' => '业务订单号',
      'width' => 160,
      'order' => '3',
      'searchtype' => 'nequal',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'channel_id' => 
    array (
      'type' => 'varchar(32)',
      'label' => '渠道ID',
      'editable' => false,
    ),
    'channel_name' => 
    array (
      'type' => 'varchar(255)',
      'label' => '渠道名称',
      'width' => 130,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => false,
    ),
    'fee_item' => 
    array (
      'type' => 'varchar(50)',
      'label'=>'费用项',
      'editable' => false,
    ),
    'fee_obj' => 
    array (
      'type' => 'varchar(10)',
      'label'=>'费用对象',
      'editable' => false,
    ),
    'fee_obj_code' => 
    array (
      'type' => 'varchar(32)',
      'label' => '费用对象编码',
    ),
    'order_type' => 
    array (
      'type' => 'varchar(32)',
      'label' => '单据类型',
      'width' => 80,
      'order' => '4',
      'editable' => false,
      'in_list' => true,
    ),
    'order_status' => 
    array (
      'type' => 'varchar(20)',
      'label' => '单据状态',
      'width' => 80,
      'order' => '5',
      'editable' => false,
      'in_list' => true,
    ),
    'money' => 
    array (
      'type' => 'money',
      'label' => '金额',
      'width' => 75,
      'order' => '6',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'balance' => 
    array (
      'type' => 'money',
      'label' => '余额',
      'editable' => false,
    ),
    'in_out_type' => 
    array (
      'type' => array(
        'in' => '收入',
        'out' => '支出'
      ),
      'label' => '收支类型',
      'width' => 70,
      'order' => '7',
      'filtertype' => 'time',
      'filterdefault'=>true,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'order_title' => 
    array (
      'type' => 'varchar(255)',
      'label' => '备注描述',
      'width' => 200,
      'order' => '8',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'trade_time' => 
    array (
      'type' => 'time',
      'label' => '账单日期',
      'width' => 130,
      'order' => '9',
      'editable' => false,
      'filtertype' => 'time',
      'filterdefault'=>true,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'trade_account' => 
    array (
      'type' => 'varchar(60)',
      'label' => '交易对方',
      'width' => 140,
      'order' => '10',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'unique' => 
    array (
      'type' => 'varchar(32)',
      'required'=>true,
      'editable' => false,
      'comment' => '数据唯一性(支付宝取交易号md5),也可用相应字段的md5'
    ),
    'create_time' => 
    array (
      'type' => 'time',
      'comment' => '创建时间',
      'width' => 130,
      'order' => '11',
      'filtertype' => 'time',
      'filterdefault'=>true,
      'required'=>true,
      'editable' => false,
      'in_list' => false,
    ),
    'memo' => 
    array (
      'type' => 'varchar(200)',
      'label' => '备注',
      'editable' => false,
    ),
  ),
  'index'=>array(
    'ind_trade_no' =>
    array (
        'columns' =>
        array (
          0 => 'trade_no',
        ),
    ),
    'ind_order_bn' =>
    array (
        'columns' =>
        array (
          0 => 'order_bn',
        ),
    ),
    'ind_trade_time' =>
    array (
      'columns' =>
      array (
        0 => 'trade_time',
      ),
    ),
    'ind_unique' =>
    array (
      'columns' =>
      array (
        0 => 'unique',
      ),
      'prefix' => 'unique',
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);