<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['logistics_examinreport']=array (
  'columns' => 
  array (
    'examin_id' => 
    array (
      'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'editable' => false
    ),
    'examin_url' => 
    array (
        'type' => 'varchar(500)',
        'label' => '报告地址',
        'editable' => false, 
        'in_list' => false,
        'default_in_list' => false,
        'is_title' => true,
        'width' => '120',
    ),    
    'examin_time' =>
    array (
      'type' => 'time',
      'label' => '体检时间',
      'width' => 130,
      'editable' => false,
      'filtertype' => 'time',
      'filterdefault' => true,
      'default_in_list' => true,
      'in_list' => true,
    ),
    'examin_status' =>
    array (
      'type' =>
      array (
        'running' => '',
        'succ' => '成功',
        'fail' => '失败',
      ),
      'default' => 'running',
      'label' => '状态',
      'width' => 75,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'examin_op_user' =>
    array (
      'label' => '体检操作人',
      'type' => 'varchar(32)',
      'width' => 130,
      'editable' => false,
      'default_in_list' => true,
      'in_list' => true,
    ),
    'config' =>
    array (
      'type' => 'text',
      'editable' => false,
    ),
   'is_first_examin' =>
    array (
      'type' => 'bool',
      'default' => 'false',
      'required' => true,
      'in_list' => true,
      'label' => '是否第一次',
    ),
    'modifytime' =>
    array (
      'type' => 'time',
      'label' => '最后修改时间',
      'editable' => false
    ),
    
  ),
  'comment' => '体检记录表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);