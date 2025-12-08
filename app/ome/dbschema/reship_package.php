<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['reship_package'] = array (
  'columns' => array (
    'package_id' => array (
        'type' => 'int unsigned',
        'required' => true,
        'pkey' => true,
        'editable' => false,
        'extra' => 'auto_increment',
        'order' => 1,
    ),
    'reship_id' => array (
        'type' => 'int unsigned',
        'required' => true,
        'editable' => false,
        'label' => '退货单ID',
    ),
    'order_id' => array (
        'type' => 'int unsigned',
        'required' => true,
        'editable' => false,
        'label' => '订单ID',
    ),
    'delivery_id' => array (
        'type' => 'int unsigned',
        'required' => true,
        'editable' => false,
        'label' => '发货单ID',
    ),
    'delivery_bn' => array (
      'type' => 'varchar(32)',
      'label' => '发货单号',
      'editable' => false,
      'width' =>140,
      'in_list' => true,
      'default_in_list' => true,
      'order' => 10,
    ),
    'wms_channel_id' => array (
        'type' => 'varchar(30)',
        'label' => '渠道ID',
        'editable' => true,
        'in_list' => true,
        'default_in_list' => true,
        'order' => 20,
    ),
    'wms_package_id' =>  array (
        'type' => 'int unsigned',
        'required' => true,
        'editable' => false,
        'label' => '发货包裹ID',
        'in_list' => false,
        'default_in_list' => false,
        'order' => 21,
    ),
    'wms_package_bn' => array (
        'type' => 'varchar(50)',
        'label' => '发货包裹单号',
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
        'order' => 22,
    ),
    'numbers' => array (
        'type' => 'int',
        'editable' => false,
        'label' => '退货总数量',
        'in_list' => false,
        'default_in_list' => false,
        'order' => 50,
    ),
    'status' => array (
        'type' => 'varchar(50)',
        'editable' => false,
        'label' => '退货包裹状态',
        'in_list' => true,
        'default_in_list' => true,
        'order' => 30,
    ),
    'sync_status' => array(
        'type' => array(
            'normal' => '未推送',
            'fail' => '推送失败',
            'succ' => '推送成功',
        ),
        'default' => 'normal',
        'width' => 75,
        'required' => true,
        'label' => '同步状态',
        'in_list' => true,
        'default_in_list' => true,
        'order' => 40,
    ),
    'wms_order_code' => array (
        'type' => 'varchar(50)',
        'label' => '京东售后申请单号',
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
        'order' => 15,
    ),
    'create_time' => array (
        'type' => 'time',
        'label' => '创建时间',
        'in_list' => true,
        'default_in_list' => true,
        'filtertype' => 'time',
        'filterdefault' => true,
        'order' => 98,
    ),
    'last_time' => array (
        'type' => 'time',
        'editable' => false,
        'label' => '最后更新时间',
        'in_list' => true,
        'default_in_list' => true,
        'order' => 99,
    ),
  ),
  'index' => 
  array (
    
    'in_reship_status' => array (
        'columns' => array (
            0 => 'reship_id',
            1 => 'sync_status',
        ),
    ),
    'in_reship_pagebn' => array (
        'columns' => array (
            0 => 'reship_id',
            1 => 'wms_package_bn',
        ),
    ),
  ),
  'comment' => '退货包裹表',
  'engine' => 'innodb',
  'version' => '$Rev: 91001 $',
);