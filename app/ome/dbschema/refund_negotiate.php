<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['refund_negotiate']=array (
  'columns' =>
  array (
    'id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'comment' => '主键',
    ),
    'refund_type' =>
        array(
            'type' => array(
                'return' => '退货',
                'refund' => '退款',
            ),
      'comment' => '退款类型',
    ),
    'original_id' =>
    array (
      'type' => 'int unsigned',
      'comment' => '来源ID',
    ),
    'original_bn' =>
    array (
      'type' => 'varchar(32)',
      'comment' => '来源编号',
    ),
    'order_id' =>
    array (
      'type' => 'varchar(32)',
      'comment' => '订单ID',
    ),
    'order_bn' =>
    array (
      'type' => 'varchar(32)',
      'comment' => '订单编号',
    ),
    'shop_id' =>
    array (
      'type' => 'varchar(32)',
      'comment' => '店铺ID',
    ),
    'negotiate_type' =>
    array (
      'type' => array(
        2 => '退款金额协商',
        4 => '退款凭证协商',
        8 => '已超售后时效',
        9 => '补发商品',
        15 => '修改运单号协商',
        16 => '退货商品异常',
        17 => '协商售后信息',
      ),
      'editable' => true,
      'label' => '协商类型',
      'comment' => '协商类型',
      'in_list' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
    'negotiate_sync_status' =>
    array (
      'type' => array(
        'none' => '不可协商',
        'pending' => '可协商',
        'succ' => '协商发起成功',
        'fail' => '协商发起失败',
        'running' => '协商发起中',
      ),
      'default' => 'pending',
      'editable' => true,
      'label' => '协商状态',
      'comment' => '协商状态',
      'in_list' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
    'negotiate_sync_msg' =>
    array (
      'type' => 'varchar(500)',
      'default' => '',
      'editable' => false,
      'label' => '协商同步消息',
      'comment' => '协商同步消息',
      'in_list' => false,
    ),
    'negotiate_desc' =>
    array (
      'type' => 'text',
      'default' => '',
      'editable' => true,
      'label' => '协商类型描述',
      'comment' => '协商类型描述',
      'in_list' => false,
    ),
    'negotiate_text' =>
    array (
      'type' => 'text',
      'default' => '',
      'editable' => true,
      'label' => '推荐话术',
      'comment' => '推荐话术',
      'in_list' => false,
    ),
    'negotiate_refund_fee' =>
    array (
      'type' => 'decimal(10,2)',
      'comment' => '建议退款金额',
    ),
    'negotiate_reason_id' =>
    array (
      'type' => 'int',
      'comment' => '建议原因ID',
    ),
    'negotiate_reason_text' =>
    array (
      'type' => 'varchar(255)',
      'comment' => '建议原因描述',
    ),
    'negotiate_address_id' =>
    array (
      'type' => 'int',
      'comment' => '收货地址ID',
    ),
    'negotiate_address_text' =>
    array (
      'type' => 'text',
      'comment' => '收货地址',
    ),
    'refund_type_code' =>
    array (
      'type' => 'int',
      'default' => 0,
      'editable' => true,
      'label' => '退款类型代码',
      'comment' => '退款类型代码',
      'in_list' => true,
    ),
    'refund_version' =>
    array (
      'type' => 'varchar(50)',
      'default' => '',
      'editable' => true,
      'label' => '退款版本号',
      'comment' => '退款版本号',
      'in_list' => false,
    ),
    'at_time' =>
    array (
      'type' => 'timestamp',
      'default' => 'CURRENT_TIMESTAMP',
      'editable' => false,
      'label' => '创建时间',
      'comment' => '创建时间',
      'in_list' => true,
      'filtertype' => 'time',
      'filterdefault' => true,
    ),
    'up_time' =>
    array (
      'type' => 'timestamp',
      'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
      'editable' => false,
      'label' => '更新时间',
      'comment' => '更新时间',
      'in_list' => true,
      'filtertype' => 'time',
      'filterdefault' => true,
    ),
  ),
  'index' =>
  array (
    'idx_original_id' =>
    array (
      'columns' =>
      array (
        0 => 'original_id',
      ),
    ),
    'idx_order_id' =>
    array (
      'columns' =>
      array (
        0 => 'order_id',
      ),
    ),
    'idx_shop_id' =>
    array (
      'columns' =>
      array (
        0 => 'shop_id',
      ),
    ),
    'idx_negotiate_type' =>
    array (
      'columns' =>
      array (
        0 => 'negotiate_type',
      ),
    ),
    'idx_negotiate_sync_status' =>
    array (
      'columns' =>
      array (
        0 => 'negotiate_sync_status',
      ),
    ),
    'idx_at_time' =>
    array (
      'columns' =>
      array (
        0 => 'at_time',
      ),
    ),
    'idx_up_time' =>
    array (
      'columns' =>
      array (
        0 => 'up_time',
      ),
    ),
    'idx_original_id_refund_type' =>
    array (
      'columns' =>
      array (
        0 => 'original_id',
        1 => 'refund_type',
      ),
      'prefix' => 'unique',
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 1 $',
  'comment' => '售后协商申请表',
  'charset' => 'utf8mb4',
);
