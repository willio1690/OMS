<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['actual_object']=array (
  'columns' =>
  array (
    'obj_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
    ),
    'eid'=>array(
      'type' => 'table:estimate@logisticsaccounts',
      'required' => true,
      'default' => 0,
      'comment' => '物流对账ID',
    ),
    'aid'=>array(
    'type' =>'table:actual@logisticsaccounts',
    'required'=>true,
    'default'=>0,
    'comment'=>'物流账单主键',
    ),
    'status'=>array(
    'type'=>array(
      0=>'否',
    1=>'是',
    ),
    'default'=>'0',
    'label'=>'是否异常'
  ),
    'memo'=>array(
    'type'=>'text',
    'label'=>'备注'
   ),
),
'index' =>
  array (
    'uni_indx' =>
    array (
      'columns' =>
      array (
       0 => 'eid',
       1 => 'aid',
      ),
      'prefix' => 'UNIQUE',
    ),
  ),
  'comment' => '物流账单对象',
  'engine' => 'innodb',
  'version' => '$Rev: 41996 $',
);