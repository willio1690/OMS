<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 基础物料关联特性数据结构
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

$db['basic_material_feature_group']=array (
  'columns' =>
  array (
    'bm_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'width' => 110,
      'hidden' => true,
      'editable' => false,
      'pkey' => true,
    ),
    'feature_group_id' =>
    array (
      'type' => 'int unsigned',
      'default' => 0,
      'required' => true,
      'hidden' => true,
      'width' => 110,
      'editable' => false,
    ),
  ),
  'comment' => '基础物料关联特性扩展表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);
