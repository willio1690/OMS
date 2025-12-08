<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['basic_material_stock_arrive']=array (
  'columns' =>
  array (
    'id' => array(
        'type'     => 'int unsigned',
        'required' => true,
        'pkey'     => true,
        'extra'    => 'auto_increment',
        'editable' => false,
    ),
    'bm_id' =>
    array (
        'type' => 'table:basic_material@material',
        'comment' => '物料ID',
        'label' => '物料',
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
    ),
    'obj_id' => array(
        'type' => 'int unsigned',
        'comment' => '对象ID',
        'label' => '对象',
        'default' => 0,
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
    ),
    'obj_type' => array(
        'type' => 'varchar(32)',
        'comment' => '对象类型',
        'label' => '对象类型',
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
    ),
    'branch_id' => array(
        'type' => 'table:branch@ome',
        'comment' => '仓库ID',
        'label' => '仓库',
        'default' => 0,
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
    ),
    'num' => array(
        'type' => 'number',
        'comment' => '在途数',
        'label' => '在途数',
        'default' => 0,
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
        'width' => 100,
        'order' => 90,
    ),
    'create_time' => array(
            'type' => 'time',
            'label' => '创建时间',
            'width' => 130,
            'in_list' => true,
            'order' => 98,
    ),
  ),
    'index' => array (
        'ind_obj_type_obj_id_branch_id_bm_id' =>
        array (
        'columns' =>
            array (
                'obj_type','obj_id','branch_id','bm_id'
            ),
            'prefix' => 'unique'
        ),
    ),
  'comment' => '在途流水表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);
