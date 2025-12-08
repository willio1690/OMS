<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['reship_items']=array (
   'columns' => array(
        'items_id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'label' => '主键id',
            'editable' => false,
        ),
        'reship_id' => array(
            'type' => 'int unsigned',
            'label' => '退供单id',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        
        'skuid' =>
        array (
          'type' => 'varchar(100)',
          'label' => '商品编号',
          'width' => 150,
         
          'editable' => false,
        
        ),
        'skuname' =>array(
            'type'      => 'varchar(200)',
            'label'     => '商品名称',

        ),
        
      
        'actualnum'=> array(
            'type' => 'number',
            'label' => '实退数量',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
       
       
    ),
    'index'   => array(
        'ind_skuid' => array('columns' => array('skuid')),
    ),
    'comment' => '退货单明细',
    'engine' => 'innodb',
    'version' => '$Rev: 40654 $',
);