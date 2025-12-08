<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['store_daliy']=array (
  'columns' =>
  array (
    'sd_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'width' => 110,
      'hidden' => true,
      'editable' => false,
      'pkey' => true,
      'extra' => 'auto_increment',
    ),
    'store_bn' =>
    array (
      'type' => 'varchar(20)',
      'required' => true,
      'label' => '门店编码',
      'editable' => false,
    ),
    'store_name' =>
    array (
      'type' => 'varchar(255)',
      'required' => true,
      'label' => '门店名称',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'order_sum' => 
    array (
      'type' => 'number',
      'default' => 0,
      'required' => true,
      'label' => '订单总数',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'sale_sum' => 
    array (
      'type' => 'number',
      'default' => 0,
      'required' => true,
      'label' => '销售货品数',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'confirm_num' => 
    array (
      'type' => 'number',
      'default' => 0,
      'required' => true,
      'label' => '审核单量',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'refuse_num' => 
    array (
      'type' => 'number',
      'default' => 0,
      'required' => true,
      'label' => '拒绝单量',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'send_num' => 
    array (
      'type' => 'number',
      'default' => 0,
      'required' => true,
      'label' => '发货单量',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'self_pick_rate' => 
    array (
      'type' => 'decimal(5,4)',
      'default' => 0.0000,
      'required' => true,
      'label' => '自提占比',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'distribution_rate' => 
    array (
      'type' => 'decimal(5,4)',
      'default' => 0.0000,
      'required' => true,
      'label' => '配送占比',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'verified_num' => 
    array (
      'type' => 'number',
      'default' => 0,
      'required' => true,
      'label' => '签收核销单量',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'createtime' =>
    array (
      'type' => 'time',
      'required' => true,
      'label' => '统计日期',
      'width' => 130,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
  ),
  'index' =>
  array (
    'ind_store_bn' =>
    array (
        'columns' =>
        array (
            0 => 'store_bn',
        ),
    ),
    'ind_createtime' =>
    array (
        'columns' =>
        array (
            0 => 'createtime',
        ),
    ),
  ),
  'comment' => '门店每日汇总统计表',
  'engine' => 'innodb',
);