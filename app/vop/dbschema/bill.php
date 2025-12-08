<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['bill']=array (
   'columns' => array(
        'id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'label' => '主键id',
            'editable' => false,
        ),
        'bill_number' => array(
            'type' => 'varchar(255)',
            'label' => '唯品会账单',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
            'width'           => 230,
            'searchtype' => 'nequal',
            'filtertype' => 'normal',
            'filterdefault' => true,
        ),
        'status' => array(
            'type' => [
                '0'=>'未确认',
                '1'=>'已确认',
                '2'=>'无需操作',
            ],
            'label' => '状态',
            'default' => '0',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'confirm_time' => array(
            'type' => 'time',
            'label' => '确认时间',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'sync_status' => array(
            'type' => [
                '0'=>'未发起',
                '1'=>'同步中',
                '2'=>'同步结束',
            ],
            'default' => '0',
            'label' => '货款同步状态',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'cr_cust_quantity' => array(
            'type' => 'money',
            'label' => '销售数量',
            'default' => '0',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
            'order' => 5,
        ),
        'cr_cust_amount' => array(
            'type' => 'decimal(20,8)',
            'label' => '销售金额',
            'default' => '0',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
            'order' => 6,
        ),
        'dr_cust_quantity' => array(
            'type' => 'money',
            'label' => '客退数量',
            'default' => '0',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
            'order' => 7,
        ),
        'dr_cust_amount' => array(
            'type' => 'decimal(20,8)',
            'label' => '客退金额',
            'default' => '0',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
            'order' => 8,
        ),
        'other_quantity' => array(
            'type' => 'money',
            'label' => '其他数量',
            'default' => '0',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
            'order' => 9,
        ),
        'other_amount' => array(
            'type' => 'decimal(20,8)',
            'label' => '其他金额',
            'default' => '0',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
            'order' => 10,
        ),
       
        'sku_count' => array(
            'type' => 'number',
            'label' => '货款行数',
            'default' => '0',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'get_count' => array(
            'type' => 'number',
            'label' => '获取货款行数',
            'default' => '0',
            
            'editable' => false,
        ),
        'discount_amount' => array(
            'type' => 'decimal(20,8)',
            'label' => '折扣金额',
            'default' => '0',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'discount_count'=>array(
            'type' => 'number',
            'label' => '折扣总行数',
            'default' => '0',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),

        'get_discount_count'=>array(
            'type' => 'number',
            'label' => '获取折扣行数',
            'default' => '0',
           
            'editable' => false,
        ),

        
        'discount_sync_status' => array(
            'type' => [
                '0'=>'未发起',
                '1'=>'同步中',
                '2'=>'同步结束',
            ],
            'default' => '0',
            'label' => '折扣同步状态',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'detail_count'=>array(
            'type' => 'number',
            'label' => '费用项行数',
            'default' => '0',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),

        'detail_amount' => array(
            'type' => 'decimal(20,8)',
            'label' => '费用金额',
            'default' => '0',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'get_detail_count'=>array(
            'type' => 'number',
            'label' => '获取费用项行数',
            'default' => '0',
          
            'editable' => false,
        ),

        'detail_sync_status' => array(
            'type' => [
                '0'=>'未发起',
                '1'=>'同步中',
                '2'=>'同步结束',
            ],
            'default' => '0',
            'label' => '费用项同步状态',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'po_sync_status' => array(
            'type' => [
                '0'=>'未发起',
                '1'=>'同步成功',
                '2'=>'同步失败',
            ],
            'default' => '0',
            'label' => 'PO单同步状态',
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
        'create_time' => array(
            'type' => 'time',
            'label' => '创建时间',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'get_time' => array(
            'type' => 'time',
            'label' => '获取时间',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),

        
        'last_modified'=>array(
            'type' => 'last_modify',
            'editable' => false,
            'in_list' => true,
            'label'=>'最后更新时间',
            'default_in_list' => true,
        ),
       
       
    ),
    'index'   => array(
        'ind_create_time' => array('columns' => array('create_time')),
        'ind_status' => array('columns' => array('status')),
        'ind_sync_status' => array('columns' => array('sync_status')),
        'ind_bill_number' => array('columns' => array('bill_number'),'prefix'=>'unique'),
    ),
    'comment' => '唯品会账单',
    'engine' => 'innodb',
    'version' => '$Rev: 40654 $',
);