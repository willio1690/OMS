<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 多选一商品规则信息表
 * 20180314 by wangjianjun
 */

$db['pickone_rules'] = array (
  'columns' => array(
    'por_id' => array(
        'type' => 'int unsigned',
        'required' => true,
        'pkey' => true,
        'extra' => 'auto_increment',
        'label' => '多选一规则主键ID',
        'hidden' => true,
        'editable' => false,
    ),
    'sm_id' => array(
        'type' => 'int unsigned',
        'required' => true,
        'label' => '销售物料ID',
        'editable' => false,
    ),
    'bm_id' => array(
        'type' => 'varchar(500)',
        'required' => true,
        'label' => '适用多个基础物料ID',
        'editable' => false,
    ),
    'sort' => array(
        'type' => 'number',
        'required' => true,
        'label' => '排序',
        'default' => '0',
        'editable' => false,
    ),
    'select_type' => array(
        'type' => 'tinyint(1)',
        'default' => '1',
        'label' => '选择方式',
    ),
  ),
  'index' => array(
    'ind_sm_id' => array('columns' => array(0 => 'sm_id')),
   ),
  'comment' => '多选一商品规则信息表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);