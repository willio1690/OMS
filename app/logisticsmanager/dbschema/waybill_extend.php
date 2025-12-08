<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['waybill_extend']=array (
  'columns' => 
  array (
    'id' => 
    array (
      'type' => 'bigint(15)',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
    ),
    'waybill_id' => 
    array (
      'type' => 'table:waybill@logisticsmanager',
      'required' => true,
      'editable' => false,
      'label' => '运单ID',
      'order' => 10,
    ),
    'mailno_barcode' =>
    array (
      'type' => 'varchar(40)',
      //'required' => true,
      'editable' => false,
      'comment' => '运单号条形码',
      'label' => '运单号条形码',
      'width' => 150,
      'in_list' => true,
      'default_in_list' => true,
      'default' => '',
      'order' => 20,
    ),
    'qrcode' => 
    array (
      'type' => 'text',
      //'required' => true,
      'default' => '',
      'editable' => false,
      'comment' => '条形码',
      'label' => '条形码',
      'width' => 100,
      'default' => '',
//      'in_list' => true,
//      'default_in_list' => true,
      'order' => 30,
    ),
    'position' => array(
      'type' => 'varchar(40)',
      'required' => true,
      'editable' => false,
      'comment' => '大头笔',
      'label' => '大头笔',
      'width' => 150,
      'default' => '',
//      'in_list' => true,
//      'default_in_list' => true,
      'order' => 40,
    ),
    'position_no' => array(
      'type' => 'varchar(40)',
      //'required' => true,
      'editable' => false,
      'comment' => '大头笔编码',
      'label' => '大头笔编码',
      'width' => 150,
      'default' => '',
//      'in_list' => true,
//      'default_in_list' => true,
      'order' => 40,
    ),
    'sort_code' => array(
      'type' => 'varchar(40)',
      'default' => '',
      'label' => '三段码',
    ),
    'package_wdjc' => array(
      'type' => 'varchar(40)',
      //'required' => true,
      'editable' => false,
      'comment' => '集包地',
      'label' => '集包地',
      'width' => 150,
      'default' => '',
//      'in_list' => true,
//      'default_in_list' => true,
      'order' => 50,
    ),
    'package_wd' => array(
      'type' => 'varchar(40)',
      //'required' => true,
      'editable' => false,
      'comment' => '集包地编码',
      'label' => '集包地编码',
      'width' => 150,
      'default' => '',
//      'in_list' => true,
//      'default_in_list' => true,
      'order' => 50,
    ),


    'print_config' => array(
      'type' => 'longtext',
      'editable' => false,
      'comment' => '菜鸟面单配置',
      'label' => '菜鸟面单配置',
      'width' => 150,
      'default' => '',
      'order' => 60,
    ),
    'json_packet' =>
    array (
      'type' => 'text',
      'editable' => false,
      //'required' => true,
      'default' => '',
      'label' => '面单数据详情',
      'width' => 80,
//      'in_list' => true,
//      'default_in_list' => true,
      'order' => 60,
    ),
  ),
  'comment' => '运单扩展表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);