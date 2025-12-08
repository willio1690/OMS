<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['store_regions']=array (
  'columns' =>
  array (
    'store_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'width' => 110,
      'hidden' => true,
      'editable' => false,
      'pkey' => true,
      'comment' => '门店ID',
    ),
    'region_1' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'width' => 110,
      'editable' => false,
      'default' => 0,
      'comment' => '一级地区',
    ),
    'region_2' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'width' => 110,
      'editable' => false,
      'default' => 0,
      'comment' => '二级地区',
    ),
    'region_3' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'width' => 110,
      'editable' => false,
      'default' => 0,
      'comment' => '三级地区',
    ),
    'region_4' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'width' => 110,
      'editable' => false,
      'default' => 0,
      'comment' => '四级地区',
    ),
    'region_5' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'width' => 110,
      'editable' => false,
      'default' => 0,
      'comment' => '五级地区',
    ),
  ),
  'comment' => '门店关联地区数据表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);