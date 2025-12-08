<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


$db['user_flow']=array (
  'columns' => 
  array (
    'user_id' => array (
      'type' => 'table:users',
      'required' => true,
      'pkey' => true,
    ),
    'flow_id' => array (
      'type' => 'table:flow',
      'required' => true,
      'pkey' => true,
      'comment' => '信息id',
    ),
    'unread' => array (
      'type' => 'bool',
      'required' => true,
      'default'=>'true',
      'comment' => '是否已读',
    ),
    'note' => array (
      'type' => 'varchar(50)',
      'default'=>'',
      'comment' => '信息',
    ),
    'has_star' => array (
      'type' => 'bool',
      'required' => true,
      'default'=>'false',
      'comment'=> '是否标记',
    ),
    'keep_unread' => array (
      'type' => 'bool',
      'required' => true,
      'default'=>'false',
      'comment' => '保持未读',
    ),
  ),
  'comment' => '管理员和信息关联表',
  'version' => '$Rev$',
  'ignore_cache' => true,
);
