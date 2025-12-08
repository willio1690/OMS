<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['warehouse_address']=array (
    'columns' =>
    array (
        'id' =>
        array (
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'editable' => false,
        ),
      'warehouse_id'=>array(
            'type' => 'number',
            'default' => '0',
            'label'=> '区域仓id',
            'width'=>100,
      ),
      'area'=>array(
        'type'          => 'region',
        'label'         => '地区',
        'width'         => 170,
        'editable'      => false,
        'filtertype'    => 'yes',
        'filterdefault' => true,
        'in_list'       => true,
      ),
      'province'=>array(
            'type' => 'varchar(50)',
            'required' => true,
            'default' => '',
            'label'=> '省',
            'width'=>100,
            'default_in_list'=>true,
            'in_list'=>true,
            'editable' => false,
        ),

        'city'=>array(
            'type' => 'varchar(50)',
            'required' => true,
            'default' => '',
            'label'=> '市',
            'width'=>100,
            'default_in_list'=>true,
            'in_list'=>true,
            'editable' => false,
        ),
        'street'=>array(
            'type' => 'varchar(50)',
            'required' => true,
            'default' => '',
            'label'=> '区',
            'width'=>100,
            'default_in_list'=>true,
            'in_list'=>true,
            'editable' => false,
        ),
        'town'=>array(
            'type' => 'varchar(50)',
            'required' => true,
            'default' => '',
            'label'=> '镇',
            'width'=>100,
            'default_in_list'=>true,
            'in_list'=>true,
            'editable' => false,
        ),
        'address' =>
        array (
            'type' => 'varchar(255)',
            'required' => true,
            'default' => '',
            'label'=> '地址',
            'width'=>100,
            'default_in_list'=>true,
            'in_list'=>true,
            'editable' => false,
        ),
        
    ),
    'index' =>
  array (
    'ind_province'=>array(
        'columns' =>
          array (
            0 => 'province',
          ),
    ),
    
  ),
  'comment' => '京标地址表',
);
