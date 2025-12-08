<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['order_settle'] = array(
	'columns' => array(
		'id'                  => array(
			'type'     => 'int unsigned',
			'required' => true,
			'pkey'     => true,
			'extra'    => 'auto_increment',
			'label'    => 'ID',
			'width'    => 110,
			'hidden'   => true,
			'editable' => false,
		),
		'order_id'            => array(
			'type'     => 'table:orders@ome',
			'required' => true,
			'editable' => false,
		),
		'type'                => array(
			'type'          => 'varchar(200)',//佣金：commission
			'default'       => '',
			'label'         => '金额类型',
			'editable'      => false,
			'filtertype'    => 'yes',
			'filterdefault' => true,
		),
		'material_bn'         => array(
			'type'            => 'varchar(200)',
			'label'           => '物料编码',
			'width'           => 120,
			'editable'        => false,
			'in_list'         => true,
			'default_in_list' => true,
			'required'        => true,
			'searchtype'      => 'nequal',
			'filtertype'      => 'normal',
			'filterdefault'   => true,
		),
		'oid'                 => array(
			'type'     => 'varchar(50)',
			'default'  => 0,
			'editable' => false,
			'label'    => '子订单号',
		),
		'real_comission'      => array(
			'type'    => 'money',
			'default' => '0.000',
			'label'   => '真实佣金',
			'width'   => 75,
		),
		'estimated_comission' => array(
			'type'    => 'money',
			'default' => '0.000',
			'label'   => '预估佣金',
			'width'   => 75,
		),
		'commission_rate'     => array(
			'type'    => 'money',
			'default' => '0.000',
			'label'   => '佣金率',
			'width'   => 75,
		),
		'extend'              => array(
			'type'     => 'serialize',
			'editable' => false,
		),
	),
	'index'   => array(
		'ind_order_oid' => array(
			'columns' => array('order_id', 'oid'),
			'prefix'  => 'unique'
		),
	),
	'comment' => '平台订单金额明细',
	'engine'  => 'innodb',
	'version' => '$Rev:  $',
	'charset' => 'utf8mb4',
);