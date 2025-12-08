<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 福袋商品订单日志表
 * 20180314 by wangjianjun
 */

$db['luckybag_log'] = array(
  'columns' => array(
    'log_id' => array(
        'type' => 'int unsigned',
        'required' => true,
        'pkey' => true,
        'extra' => 'auto_increment',
        'label' => '福袋商品订单日志主键ID',
        'hidden' => true,
        'editable' => false,
    ),
    'order_id' => array (
        'type' => 'int unsigned',
        'required' => true,
        'label' => '订单ID',
        'editable' => false,
    ),
    'shop_id' => array (
        'type' => 'varchar(32)',
        'required' => true,
        'label' => '店铺ID',
        'editable' => false,
    ),
    'lbr_id' => array (
        'type' => 'int unsigned',
        'required' => true,
        'label' => '福袋规则ID',
        'editable' => false,
    ),
    'sm_id' => array (
        'type' => 'int unsigned',
        'required' => true,
        'label' => '销售物料ID',
        'editable' => false,
    ),
    'bm_id' => array (
        'type' => 'int unsigned',
        'required' => true,
        'label' => '基础物料ID',
        'editable' => false,
    ),
    'num' => array (
        'type' => 'number',
        'required' => true,
        'label' => '数量',
        'editable' => false,
    ),
    'price' => array (
        'type' => 'money',
        'required' => true,
        'label' => '售价',
        'editable' => false,
    ),
    'create_time' => array (
        'type' => 'time',
        'required' => true,
        'label' => '创建时间',
        'editable' => false,
    ),
    'update_time' => array (
        'type' => 'time',
        'label' => '更新时间',
        'editable' => false,
    ),
  ),
  'index' => array(
    'ind_order_id' => array('columns' => array(0 => 'order_id')),
    'ind_shop_id' => array('columns' => array(0 => 'shop_id')),
    'ind_sm_id' => array('columns' => array(0 => 'sm_id')),
    'ind_bm_id' => array('columns' => array(0 => 'bm_id')),
  ),
  'comment' => '福袋商品订单日志表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);
