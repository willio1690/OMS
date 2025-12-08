<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['vopbill_amount']=array (
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
        'bill_number' => array(
            'type' => 'varchar(50)',
            'label' => '唯品会账单',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
            'width'           => 230,
            'searchtype' => 'nequal',
            'filtertype' => 'normal',
            'filterdefault' => true,
        ),
       
        'barcode' => array(
            'type' => 'varchar(255)',
            'label' => '商品条形码',
            'comment' => 'itemNo',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'product_name' => array(
            'type' => 'varchar(255)',
            'label' => '商品名称',
            'comment' => 'itemDescription',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'detail_line_type' => array(
            'type' => 'varchar(255)',
            'label' => '行类型',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'detail_line_name' => array(
            'type' => 'varchar(255)',
            'label' => '行类型名称',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'shop_id' => array(
            'type' => 'table:shop@ome',
            'label' => '店铺',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'qty' => array(
            'type' => 'money',
            'label' => '数量',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'amount' => array(
            'type' => 'decimal(20,2)',
            'label' => '含税金额',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
            'default'=>0,
        ),

        'discount_amount'=>array(
            'type' => 'decimal(20,2)',
            'label' => '折扣金额',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
            'default'=>0,

        ),
        
        'total_amount'=>array(
            'type' => 'decimal(20,2)',
            'label' => '合计金额',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
            'default'=>0,
        ),
           
    ),
    'index'   => array(
        'ind_bill_id' => array('columns' => array('bill_id')),
     
        'ind_barcode' => array('columns' => array('barcode')),
        'ind_detail_line_type_unique' => array('columns' => array('detail_line_type','bill_id','barcode'),'prefix'=>'unique'),
        'ind_detail_line_name' => array('columns' => array('detail_line_name')),
    ),
    'comment' => '唯品会账单明细',
    'engine' => 'innodb',
    'version' => '$Rev: 40654 $',
);