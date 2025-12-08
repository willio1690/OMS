<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 特性配置数据结构
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

$db['feature']=array (
  'columns' =>
  array (
    'ft_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'width' => 110,
      'hidden' => true,
      'editable' => false,
    ),
    'ft_name' => 
    array (
      'type' => 'varchar(200)',
      'required' => true,
      'editable' => false,
      'is_title' => true,
      'searchtype' => 'has',
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
      'width' => 200,
      'label' => '特性名称',
    ),
    'type' =>
    array (
      'required' => true,
      'type' => 'varchar(50)',
      'editable' => false,
      'label' => '事件节点',
      'in_list' => true,
      'default_in_list' => true,
      'width' => 160,
    ),
    'config' =>
    array (
      'type' => 'serialize',
      'editable' => false,
    ),
    'disabled' => 
    array (
      'type' => 'bool',
      'required' => true,
      'editable' => false,
      'default' => 'false',
    ),
  ),
'index' =>
  array (
    'ind_ft_name' =>
    array (
        'columns' =>
        array (
          0 => 'ft_name',
        ),
        'prefix' => 'unique',
    ),
  ),
  'comment' => '特性配置表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);
