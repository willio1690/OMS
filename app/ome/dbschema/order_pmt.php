<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['order_pmt']=array (
  'columns' => 
  array (
    'id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'order_id' =>
    array (
      'type' => 'table:orders@ome',
      'required' => true,
      'editable' => false,
    ),
    'pmt_amount' =>
    array (
      'type' => 'money',
      'editable' => false,
    ),
    'pmt_memo' =>
    array (
      'type' => 'longtext',
      'edtiable' => false,
    ),
    'pmt_describe' =>
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
    'coupon_id' => array(
        'type' => 'varchar(32)',
        'label' => '优惠券ID',
        'in_list' => false,
        'default_in_list' => false,
    ),
    'up_time'           => [
        'type'    => 'TIMESTAMP',
        'label'   => '更新时间',
        'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        'width'           => 130,
        'in_list' => true,
    ],
  ), 
  'index' => [
        'ind_up_time' => ['columns' => ['up_time']],
  ],
  'comment' => '订单促销规则',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);