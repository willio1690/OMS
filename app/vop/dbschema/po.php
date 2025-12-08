<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['po']=array (
   'columns' => array(
        'po_id' => array(
            'type' => 'int unsigned',
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
            'type' => 'varchar(255)',
            'label' => '唯品会账单',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
            'width'           => 230,
            'searchtype' => 'nequal',
            'filtertype' => 'normal',
            'filterdefault' => true,
            'order' => 1,
        ),
        'po_no' => array(
            'type' => 'varchar(30)',
            'label' => 'PO编号',
            'in_list'         => true,
            'default_in_list' => true,
            'filtertype' => 'normal',
            'filterdefault' => true,
            'order' => 2,
        ),

        'bill_type' => 
        array (
            'type' => 'varchar(32)',
            'required' => true,
            'label'=>'业务类型',
            'width' => 120,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'order'=>3,
            'filtertype' => 'normal',
            'filterdefault' => true,
        ),
        'bill_type_name'=>array(
            'label' => '行类型名称',
            'type' => 'varchar(50)',
            'in_list'         => true,
            'default_in_list' => true,
            'order' => 4,
        ),
        'quantity' => array(
            'type' => 'money',
            'label' => '销售数量',
            'default' => '0',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
            'order' => 5,
        ),

        'amount' => array(
            'type' => 'decimal(20,8)',
            'label' => '销售金额',
            'default' => '0',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
            'order' => 6,
        ),
        

        'signtime'  =>array(
            'type' => 'time',
            'label' => '账单签发时间',
            'in_list'         => true,
            'default_in_list' => true,
            'order' => 7,
        ),
        'shop_id' => array(
            'type' => 'table:shop@ome',
            'label' => '店铺',
             'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
            'order' => 8,
        ),
        'create_time' => array(
            'type' => 'time',
            'label' => '创建时间',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
            'order' => 9,
        ),
        'sync_status' => array(
            'type' => [
                '0'=>'未发起',
                '1'=>'同步成功',
                '2'=>'同步失败',
            ],
            'default' => '0',
            'label' => '同步状态',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
            'order' => 10,
        ),
        
        
        'last_modified'=>array(
            'type' => 'last_modify',
            'editable' => false,
            'in_list'         => true,
            'default_in_list' => true,
            'label'=>'最后更新时间',
            
        ),
       
       
    ),
    'index'   => array(
      
        'ind_sync_status' => array('columns' => array('sync_status')),
        'ind_bill_number' => array('columns' => array('bill_number')),
    ),
    'comment' => '唯品会账单',
    'engine' => 'innodb',
    'version' => '$Rev: 40654 $',
);