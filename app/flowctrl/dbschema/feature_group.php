<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 类目数据结构
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

$db['feature_group']=array (
  'columns' =>
  array (
    'ftgp_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'width' => 110,
      'hidden' => true,
      'editable' => false,
    ),
    'ftgp_name' => 
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
      'width' => 150,
      'label' => '类目',
    ),
    'config' =>
    array (
      'type' => 'text',
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
    'ind_ftgp_name' =>
    array (
        'columns' =>
        array (
          0 => 'ftgp_name',
        ),
        'prefix' => 'unique',
    ),
  ),
  'comment' => '特性类目配置表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);