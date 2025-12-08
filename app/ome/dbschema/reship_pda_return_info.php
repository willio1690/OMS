<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['reship_pda_return_info']=array ( 
  'columns' =>
  array (
    'id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
    ),
    'item_id' =>
    array (
      'type' => 'table:reship_items@ome',
      'required' => true,
      'editable' => false,
      'comment' => '明细ID',
    ),
    'reship_id' =>
    array (
      'type' => 'table:reship@ome',
      'editable' => false,
      'required' => true,
      'comment' => '退换货单号',
    ),
    'product_id' =>
    array (
      'type' => 'int unsigned',
      'editable' => false,
      'comment' => '货品ID',
    ),
     'defective_num' =>
    array (
      'type' => 'number',
      'editable' => false,
      'default' => 0,
      'label' => '异常退货数量',
    ),
    'normal_num' =>
    array (
      'type' => 'number',
      'editable' => false,
      'default' => 0,
      'label' => '正常退货数量',
    ),
    'remark' =>
    array (
      'type' => 'varchar(100)',
      'editable' => false,
      'comment' => '备注',
    ),
    'abnormalbit' =>
    array (
      'type' => array(
          0=>'正常',
          1=>'异常',
      ),
      'editable' => false,
      'comment' => '异常状态',
    ),
    'reason' =>
    array (
      'type' => 'text',
      'editable' => false,
      'comment' => '异常原因',
    ),
    'scenepicture' =>
    array (
      'type' => 'text',
      'editable' => false,
      'comment' => '现场图片',
    ),
    'number' =>
    array (
      'type' => 'varchar(50)',
      'editable' => false,
      'comment' => 'pda退货序列号',
    ),
    'return_branch_id' => 
    array (
      'type' => 'table:branch@ome',
      'comment' => '退货仓库ID',
      'editable' => false,
    ),
    'op_id' => 
    array (
      'type' => 'table:account@pam',
      'editable' => false,
    ),
    'op_name' =>
    array (
      'type' => 'varchar(30)',
      'editable' => false,
    ),
    'add_time' =>
    array (
      'type' => 'time',
      'editable' => false,
       'label' => 'pda请求时间',
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);