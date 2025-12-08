<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['vopreturn_items']=array (
   'columns' => array(
        'id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'label' => '主键id',
            'editable' => false,
        ),
        'return_id' => array(
            'type' => 'table:vopreturn@console',
            'label' => '退供单号',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'shop_product_bn' =>
        array (
          'type' => 'varchar(30)',
          'label' => '店铺货号',
          'width' => 150,
         
          'editable' => false,
        
        ),
        'material_bn' =>
        array (
          'type' => 'varchar(30)',
          'label' => '货号',
          'width' => 150,
         
          'editable' => false,
        
        ),
        'bm_id' =>
        array (
        'type' => 'int unsigned',
        'label' => '商品ID',
        'editable' => false,
        'in_list' => false,
        'default_in_list' => false,
        'comment' => '基础物料ID,关联material_basic_material.bm_id'
        ),
        'barcode' => array(
            'type' => 'varchar(255)',
            'label' => '商品条形码',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'partcode'=> array(
            'type' => 'varchar(50)',
            'label' => '备件条码',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'product_name' => array(
            'type' => 'varchar(255)',
            'label' => '商品名称',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        
        'price' =>
        array(
            'type' => 'money',
            'default' => '0.000',
            'label' => '价格',
            'width' => 75,
            'comment'=>'价格',
        ),
        'grade' => array(
            'type' => 'number',
            'label' => '货品等级',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'po_no' => array(
            'type' => 'varchar(255)',
            'label' => '采购订单号',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'qty' => array(
            'type' => 'number',
            'label' => '实退数量',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'split_num' => array(
            'type' => 'number',
            'label' => '拆分数量',
            'default' => 0,
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'num' => array(
            'type' => 'number',
            'label' => '入库数量',
            'default' => 0,
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'box_no' => array(
            'type' => 'varchar(255)',
            'label' => '退供箱号',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'storage_no' => array(
            'type' => 'varchar(255)',
            'label' => '供应商入库单号',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'storage_box_no' => array(
            'type' => 'varchar(255)',
            'label' => '供应商入库单箱号',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'saleordid'=>array(
            'label'           => '平台原始订单号',
            'type'            => 'varchar(100)',
        ),
        'transferoutcode'=>array(
            'label'           => '出库单号',
            'type'            => 'varchar(100)',
        ),
        'originsaleordid'=>array(
            'label'           => '原始销售订单号',
            'type'            => 'varchar(100)',
        ),
        'refundid'=>array(
            'type'            => 'varchar(150)',
            'label'           => '退货单号',
            'default'         => '',
           
        ),
        'difference_no'=>array(
            'label'           => '差异单号',
            'type'            => 'varchar(100)',
        ),
        'source' => array(
            'type'     => array(
                'matrix' => '平台获取',
                'local'  => '手工新增',
            ),
            'label'    => '来源',
            'default'  => 'matrix',
            'editable' => false,
        ),
    ),
    'index'   => array(
        'ind_barcode' => array('columns' => array('barcode')),
        'ind_refundid'=>array('columns' => array('refundid')),
        'ind_transferoutcode'=>array('columns' => array('transferoutcode')),
    ),
    'comment' => '唯品会退货单明细',
    'engine' => 'innodb',
    'version' => '$Rev: 40654 $',
);
