<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['source_billgoods_ext']=array (
   'columns' => array(
        'id' => array(
            'type' => 'bigint unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'label' => '主键id',
            'editable' => false,
        ),
         'bill_id' => array(
            'type' => 'int unsigned',
            'label' => '唯品会账单',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'source_billgoods_id' => array(
            'type' => 'int unsigned',
            'label' => '货款账单id',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ), 
        'addon'=>array(
            'type' => 'longtext',
            'label' => '源数据',
            'in_list'         => false,
            'default_in_list' => false,
            'editable' => false,
        ),
        'at_time'        => [
            'type'    => 'TIMESTAMP',
            'label'   => '创建时间',
            'default' => 'CURRENT_TIMESTAMP',
            'width'   => 120,
            'in_list' => false,
            'order'   => 11,
        ],
        'up_time'        => [
            'type'    => 'TIMESTAMP',
            'label'   => '更新时间',
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'width'   => 120,
            'in_list' => false,
            'order'   => 11,
        ],
       
       
    ),
    'index'   => array(
        'ind_source_billgoods_id' => array('columns' => array('source_billgoods_id'),'prefix'=>'unique'),
        'ind_bill_id' => array('columns' => array('bill_id')),

        
    ),
    'comment' => '唯品会账单明细',
    'engine' => 'innodb',
    'version' => '$Rev: 40654 $',
);