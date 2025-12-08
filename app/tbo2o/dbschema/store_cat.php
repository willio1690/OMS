<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['store_cat']=array (
  'columns' =>
  array (
    'stc_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'width' => 110,
      'hidden' => true,
      'editable' => false,
      'pkey' => true,
      'extra' => 'auto_increment',
    ),
    'cat_id' =>
    array (
      'type' => 'int(10)',
      'label' => '类目ID',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'cat_name' =>
    array (
      'type' => 'varchar(20)',
      'label' => '类目名称',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'p_stc_id' =>
    array (
      'type' => 'int(10)',
      'label' => '上级类目ID',
      'editable' => false,
    ),
    'cat_path' =>
    array (
        'type' => 'varchar(255)',
        'width'=>300,
        'editable' => false,
        'comment' => '分类路径',
    ),
    'cat_grade' =>
    array (
        'type' => 'number',
        'editable' => false,
        'comment' => '路径级数',
    ),
    'haschild' => array(
        'type' => 'tinyint(1)',
        'default' => 0,
        'label' => '是否存在下级',
    ),
  ),
  'index' =>
    array (
        'ind_cat_id' =>
        array (
            'columns' =>
            array (
                0 => 'cat_id',
            ),
        ),
   ),
  'comment' => '淘宝门店类目表',
  'engine' => 'innodb',
);