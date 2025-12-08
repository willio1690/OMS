<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


$db['recycle']=array (
  'columns' => 
  array (
    'item_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'item_title' => 
    array (
      'type' => 'varchar(200)',
      'label'=>app::get('desktop')->_('名称'),
      'required' => false,
      'is_title'=>true,
      'in_list'=>true,
      'width'=>200,
      'filtertype' => 'yes',
      'filterdefault' => true,
      'default_in_list'=>true,
    ),
    'item_type'=>array(
      'label'=>app::get('desktop')->_('类型'),
      'type' => 'varchar(80)',
      'required' => true,
      'in_list'=>true,
      'width'=>100,
      'filtertype' => 'yes',
      'filterdefault' => true,

      'default_in_list'=>true,
    ),
    'app_key'=>array(
      'label'=>app::get('desktop')->_('应用'),
      'type' => 'varchar(80)',
      'required' => true,
      'in_list'=>true,
      'width'=>100,
      'default_in_list'=>true,
    ),
    'drop_time'=>array(
      'type' => 'time',
      'label'=>app::get('desktop')->_('删除时间'),
      'required' => true,
      'in_list'=>true,
      'width'=>150,
      'filtertype' => 'yes',
      'filterdefault' => true,
      'default_in_list'=>true,
    ),
    'item_sdf'=>array(
      'type' => 'serialize',
      'required' => true,
      'comment' => '标准数据结构',
    ),
  ),
  'comment' => '回收站',
  'engine' => 'innodb',
  'version' => '$Rev: 40912 $',
);