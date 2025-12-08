<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


$db['analysis_logs']=array (
  'columns' =>
  array (
    'id' =>
    array (
      'type' => 'bigint unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'analysis_id' =>
    array (
      'type' => 'number',
      'required' => true,
      'comment' => '报表ID',
    ),
    'type' =>
    array (
      'type' => 'number',
      'required' => true,
      'label' => '类型',
      'default' => 0,
    ),
    'target' =>
    array (
      'type' => 'number',
      'required' => true,
      'label' => '指标',
      'default' => 0,
    ),
    'flag' =>
    array (
      'type' => 'number',
      'required' => true,
      'label' => '标识',
      'default' => 0,
    ),
    'value' =>
    array (
      'type' => 'float',
      'required' => true,
      'label' => '数据',
      'default' => 0,
    ),
    'time' =>
    array (
      'type' => 'time',
      'required' => true,
      'label' => '时间',
    ),
  ),
  'index' =>
      array (
        'ind_analysis_id' =>
        array (
          'columns' =>
          array (
            0 => 'analysis_id',
          ),
        ),
        'ind_type' =>
        array (
          'columns' =>
          array (
            0 => 'type',
          ),
        ),
        'ind_target' =>
        array (
          'columns' =>
          array (
            0 => 'target',
          ),
        ),
        'ind_time' =>
        array (
          'columns' =>
          array (
            0 => 'time',
          ),
        ),
    ),
  'comment' => '电商商务通用应用分析记录',
  'ignore_cache' => true,
);

