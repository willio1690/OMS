<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['replenish_suggest_items'] = array(
    'columns' => array(
        'item_id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'label' => 'ID',
            'width' => 110,
            'hidden' => true,
            'editable' => false,
        ),
        'sug_id' => array(
            'type' => 'int unsigned',
            
            'width'    => 110,
            'hidden'   => true,
            'editable' => false,
        ),

       'bm_id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'width'    => 110,
            'hidden'   => true,
            'editable' => false,
        ),
        'material_bn' => array (
          'type' => 'varchar(30)',
          'label' => '基础物料编码',
          'width' => 120,
          'editable' => false,
          'in_list' => true,
          'default_in_list' => true,
          'required' => true,
          'searchtype' => 'nequal',
          'filtertype' => 'textarea',
          'filterdefault' => true,
          'order' => 35,
        ),
        
        'store_num' => array (
            'type' => 'number',
            'editable' => false,
            'label' => '门店可用库存',
            'default' => 0,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 68,
        ),
        
        'apply_nums' => array (
            'type' => 'number',
            'editable' => false,
            'label' => '申请补货数量',
            'default' => 0,
            'in_list' => false,
            'default_in_list' => false,
            'order' => 39,
        ),
        'reple_nums' => array (
            'type' => 'number',
            'editable' => false,
            'label' => '实际补货数量',
            'default' => 0,
            'in_list' => false,
            'default_in_list' => false,
            'order' => 38,
        ),
        'price' =>
        array (
          'type' => 'money',
          'editable' => false,
        ),
        
    ),
    'index' => array(
       
        'in_material_bn' => array(
            'columns' => array('material_bn'),
        ),
    ),
    'engine'  => 'innodb',
    'version' => '$Rev: $',
    'comment' => '补货建议单据表',
);
