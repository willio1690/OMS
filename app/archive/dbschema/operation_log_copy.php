<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['operation_log_copy']=array (
  'columns' => 
  array (
    'log_id' => 
    array (
      'type' => 'int unsigned',
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
      'comment' => '日志ID',
    ),
    'obj_id' => 
    array (
      'type' => 'varchar(50)',
      'required' => true,
      'editable' => false,
      'comment' => '操作对象主键',
    ),
    'obj_name' =>
    array (
      'type' => 'varchar(200)',
      'editable' => false,
      'comment' => '操作对象含义字段（dbschema中的is_title=true字段值）',
    ),
    'obj_type' => 
    array (
      'type' => 'varchar(30)',
      'required' => true,
      'editable' => false,
      'comment' => '操作日志类型',
    ),
    'operation' => 
    array (
      'type' => 'varchar(50)',
      'editable' => false,
      'required' => true,
      'label' => '操作名'
    ),
    'op_id' => 
    array (
      'type' => 'table:account@pam',
      'editable' => false,
      'required' => true,
      'comment' => '操作员ID',
    ),
    'op_name' =>
    array (
      'type' => 'varchar(30)',
      'editable' => false,
      'comment' => '操作员',
    ),
    'operate_time' => 
    array (
      'type' => 'time',
      'required' => true,
      'editable' => false,
      'comment' => '处理时间',
    ),
    'memo' => 
    array (
      'type' => 'text',
      'editable' => false,
      'comment' => '备注',
    ),
    'ip' => 
    array (
      'type' => 'varchar(15)',
      'editable' => false,
      'comment' => 'ip',
    ),
  ),
  'index' =>
  array (
    'ind_obj_id' => 
    array (
      'columns' =>
      array (
        0 => 'obj_id',
      ),
    ),
    'ind_obj_type' =>
    array (
      'columns' => 
      array (
        0 => 'obj_type',
      ),
    ),
  ),
  'comment' => '归档操作员操作记录备份表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);