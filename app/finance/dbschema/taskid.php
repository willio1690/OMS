<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['taskid']=array (
  'comment' => '交易任务号',
  'columns' => 
  array (
    'task_id' => 
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'label' => '任务号',
    ),
    'node_id' => 
    array (
      'type' => 'varchar(20)',
      'pkey' => true,
      'editable' => false,
      'label' => '节点ID',
    ),
    'node_name' => 
    array (
      'type' => 'varchar(32)',
      'editable' => false,
      'label' => '节点名称',
    ),
    'taskid_time' =>
    array (
      'type' => 'varchar(25)',
      'label' => '任务创建时间',
      'editable' => false,
    ),
    'start_time' =>
    array (
      'type' => 'varchar(25)',
      'label' => '单据开始时间',
      'editable' => false,
    ),
    'end_time' =>
    array (
      'type' => 'varchar(25)',
      'label' => '单据结束时间',
      'editable' => false,
    ),
    'createtime' =>
    array (
      'type' => 'time',
      'label' => '记录创建时间',
      'editable' => false,
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
