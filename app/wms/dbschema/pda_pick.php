<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

#pda拣货关联
$db['pda_pick']=array (
  'columns' => array (
      'id' =>array (
          'type' => 'int unsigned',
          'required' => true,
     	   'pkey' => true,
	  	   'editable' => false,
	  	   'extra' => 'auto_increment',
	  ),
     'delivery_id' => array (
	     'type' => 'table:delivery@ome',
	     'required' => true,
	     'editable' => false
     ),
     'delivery_bn' => array (
	     'type' => 'varchar(32)',
	     'comment' => '发货单号',
	     'editable' => false,
     ),
     'box' => array (
     		'type' => 'varchar(50)',
     		'comment' => '篮子号',
     		'default' => '',
     		'editable' => false,
     ),
  	 'status' =>array (
  		'type' =>
  			array (
  					'0' => '未拣货',
  					'1' => '拣货中',
  					'2' => '已拣货',
  			),
  			'default' => '0',
  			'comment' => '拣货认领状态',
  			'editable' => false,
  	 ),
     'create_time' =>  array (
     	 'type' => 'time',
     	 'comment' => '单据生成时间',
     	 'editable' => false,
     ),
     'op_id' =>
     array (
         'type' => 'varchar(50)',
         'editable' => false,
     ),
  ),
  'index' => 
  array (
    'ind_delivery_bn' =>
    array (
        'columns' =>
        array (
            0 => 'delivery_bn',
        ),
        'prefix' => 'unique',
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 41996 $',
);