<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['fukubukuro_log'] = array(
  'columns' => array(
    'log_id' => array(
        'type' => 'int unsigned',
        'required' => true,
        'pkey' => true,
        'extra' => 'auto_increment',
        'label' => '日志ID',
        'hidden' => true,
        'editable' => false,
    ),
    'order_id' => array (
        'type' => 'int unsigned',
        'required' => true,
        'label' => '订单ID',
        'editable' => false,
    ),
    'combine_id' => array (
        'type' => 'int unsigned',
        'required' => true,
        'label' => '福袋组合规则ID',
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
    'quantity' => array (
        'type' => 'number',
        'required' => true,
        'label' => '分配数量',
        'editable' => false,
    ),
    'create_time' => array (
        'type' => 'time',
        'required' => true,
        'label' => '创建时间',
        'editable' => false,
    ),
  ),
  'index' => array(
    'ind_order_id' => array('columns' => array(0 => 'order_id')),
    'ind_sm_id' => array('columns' => array(0 => 'sm_id')),
    'ind_bm_id' => array('columns' => array(0 => 'bm_id')),
    'ind_combine_id' => array('columns' => array(0 => 'combine_id')),
  ),
  'comment' => '订单分配福袋日志表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);
