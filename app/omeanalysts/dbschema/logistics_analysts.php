<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['logistics_analysts']=array (
  'columns' => 
  array (
    'id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'label' => 'ID',
      'width' => 110,
      'hidden' => true,
      'editable' => false,
	  'filtertype' => 'normal',
    ),
    'branch_id' =>  
    array (
      'type' => 'number',
      'label' => '仓库',
      'width' => 75,
      'editable' => false,
    ),
    'logi_id' => array (
      'type' => 'number',
      'label' => '物流公司',
      'in_list' => true,
    ),
    'trace_date' => 
    array (
      'type' => 'varchar(15)',
      'required' => true,
    'label' => '记录日期',
      'editable' => false,
      'in_list' => true,
    ),
	'delivery_num' => 
    array (
      'type' => 'mediumint',
     'label' => '发货数量',
      'default' => 0,
      'editable' => false,
      'in_list' => true,
    ),
	'embrace_num' => 
    array (
      'type' => 'mediumint',
     'label' => '揽收数量',
      'default' => 0,
      'editable' => false,
      'in_list' => true,
    ),
    'sign_num' => 
    array (
      'type' => 'mediumint',
     'label' => '签收数量',
      'default' => 0,
      'editable' => false,
      'in_list' => true,
    ),
    'problem_num' => 
    array (
      'type' => 'mediumint',
     'label' => '问题件',
      'default' => 0,
      'editable' => false,
      'in_list' => true,
    ),
    'timeout_num' => 
    array (
      'type' => 'mediumint',
     'label' => '配送超时数量',
      'default' => 0,
      'editable' => false,
      'in_list' => true,
    ),
  ),
  'comment' => '仓储物流配送统计表',
  'index' =>
  array (
    'ind_trace_date' =>
    array (
        'columns' =>
        array (
          0 => 'trace_date',
        ),
    ),
    'ind_branch_id' =>
    array (
        'columns' =>
        array (
          0 => 'branch_id',
        ),
    ),
    'ind_logi_id' =>
    array(
      'columns' => 
      array(0=>'logi_id'),
     
    ),
    
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
