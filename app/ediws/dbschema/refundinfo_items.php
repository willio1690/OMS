<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['refundinfo_items'] = array(
    'columns' => array(
        
        'items_id'                => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'label'    => '主键id',
            'editable' => false,
        ),
       'refundinfo_id'=>array(
            'type'     => 'int unsigned',
            'required' => true,

       ),
     
        'barcode'=> array(
            'type' => 'varchar(50)',
            'label' => '条码',
            'in_list'         => true,
            'default_in_list' => true,
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
       'wareid'       => array(
            'type'            => 'varchar(100)', 
            'label'           => '商品编号',

        ),
       'signtime'       => array(
            'type'            => 'time',
            'label'           => '确认收货时间',

        ),
       
       'remark'=>array(
            'label'           => '备注',
            'type'            => 'varchar(255)',
       ),
       'partcode'=>array(
            'label'           => '备件条码',
            'type'            => 'varchar(100)',
        ),
       'warename'=>array(
            'label'           => '商品名称',
            'type'            => 'varchar(100)',
        ),

       'saleordid'=>array(
            'label'           => '平台原始订单号',
            'type'            => 'varchar(100)',
        ),

       'shipcode'=>array(
            'label'           => '运单号',
            'type'            => 'varchar(100)',
        ),

       'confirmreceiptpeople'=>array(
            'type'            => 'varchar(100)',
            'label'           => '确认收货人',

        ),
        'price' =>array(
            'type'      => 'decimal(20,3)',
            'label'     => '退货价格',

        ),
       
        'returnReason'=>array(
            'type'      => 'varchar(100)',
            'label'     => '退货原因',
        ),
        

    
        
    ),
    'index'   => array(
        'ind_refundinfo_id' => array(
            'columns' => array(
                0 => 'refundinfo_id',
            ),
        ),
        'ind_order_barcode' => array(
            'columns' => array(
                0 => 'saleordid',
                1 => 'wareid',
                2 => 'partcode',
            ),
        ),
        'ind_bill_partcode' => array(
            'columns' => array(
                0 => 'partcode',
               
             
            ),
        ),
    ),
    'comment' => '售后退货详情',
    'engine'  => 'innodb',
    'version' => '$Rev: 40654 $',
);
