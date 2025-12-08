<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['vopbill_detail']=array (
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
          
            'editable' => false,
        ),
        'bill_number' => array(
            'type' => 'varchar(50)',
            'label' => '唯品会账单号',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
            'width'           => 230,
            'searchtype' => 'nequal',
            'filtertype' => 'normal',
            'filterdefault' => true,
        ),
        
        'globalid' => array(
            'type' => 'bigint unsigned',
            'label' => '唯品会行ID',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'barcode' => array(
            'type' => 'varchar(255)',
            'label' => '商品条形码',
            'comment' => 'itemNo',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
       
        'quantity'=>array(
            'type' => 'money',
            'label' => '数量',
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
        'source'=>array(
            'type' => 'tinyint(1)',
            'label' => '数据来源',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'sourcetype'=>array(
            'type' => 'varchar(50)',
            'label'=>'源类型',
            'in_list'         => true,
            'default_in_list' => true,

        ),
      
        'amount' => array(
            'type' => 'decimal(20,8)',
            'label' => '金额',
          
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'targetamount'=>array(

            'type' => 'decimal(20,8)',
            'label' => '换汇金额',
          
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,

        ),
        'expid'=>array(
            'type' => 'varchar(150)',
            'label'=>'外部系统费用单号',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'extordernum'=>array(
            'type' => 'varchar(150)',
            'label'=>'订单号',
            'in_list'         => true,
            'default_in_list' => true,

        ),

        'feeitem'=>array(
            'type' => 'varchar(50)',
            'label'=>'收费项目',
            'in_list'         => true,
            'default_in_list' => true,

        ),

        'addon' => array(
            'type' => 'longtext',
            'label' => '源数据',
            'in_list'         => false,
            'default_in_list' => false,
            'editable' => false,
        ),
    ),
    'index'   => array(
        'ind_globalid' => array('columns' => array('globalid'),'prefix'=>'unique'),
        'ind_bill_id' => array('columns' => array('bill_id')),   
        'ind_barcode' => array('columns' => array('barcode')),
        
    ),
    'comment' => '唯品会费用项明细',
    'engine' => 'innodb',
    'version' => '$Rev: 40654 $',
);