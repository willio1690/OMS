<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 销售物料扩展数据结构
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

$db['sales_material_ext']=array (
  'columns' =>
  array (
    'sm_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'width' => 110,
      'hidden' => true,
      'editable' => false,
      'pkey' => true,
      'comment' => '销售物料ID,关联material_sales_material.sm_id'
    ),
    'cost' =>
    array (
      'type' => 'money',
      'default' => '0.000',
      'label' => '成本价',
      'width' => 110,
      'comment'=>'销售物料固定成本价',
    ),
    'lowest_price' =>
    array (
      'type' => 'money',
      'default' => '0.000',
      'label' => '最低售价',
      'width' => 75,
      'comment'=>'销售物料最低售价',
    ),
    'retail_price' =>
    array (
      'type' => 'money',
      'default' => '0.000',
      'label' => '零售价',
      'width' => 75,
      'comment'=>'销售物料零售价',
    ),
    'weight' =>
    array (
      'type' => 'decimal(20,3)',
      'label' => '重量',
      'default' => '0.000',
      'width' => 110,
      'editable' => false,
      'comment' => '销售物料重量,单位:g',
    ),
    'unit' =>
    array (
      'type' => 'varchar(20)',
      'label' => '包装单位',
      'width' => 100,
      'editable' => false,
    ),
    'brand_id' =>
    array(
        'type' => 'table:brand@ome',
        'label' => '品牌id',
        'width' => 150,
        'editable' => false,
        'comment' => '品牌ID,关联ome_brand.brand_id',
    ),
    'cat_id' => array (
        'type' => 'table:basic_material_cat@material',
        'required' => false,
        'default' => 0,
        'label' => '分类',
        'width' => 75,
        'editable' => true,
        'filtertype' => 'yes',
        'filterdefault' => true,
        'in_list' => true,
        'default_in_list' => true,
        'comment' => '分类ID,关联material_basic_material_cat.cat_id'
    ),
    'cat_path' => array (
        'type' => 'varchar(100)',
        'default' => '',
        'label' => '分类路径',
        'width' => 110,
        'editable' => false,
        'in_list' => true,
        'comment' => '分类路径(从根至本结点的路径,逗号分隔)',
    ),
  ),
  'comment' => '销售物料扩展表,用于存储销售物料的额外属性信息',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);
