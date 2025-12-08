<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['source_discount']=array (
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
        'shop_id' => array(
            'type' => 'table:shop@ome',
            'label' => '店铺',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'global_id' => array(
            'type' => 'bigint unsigned',
            'label' => '唯品会明细ID',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'vendor_id' => array(
            'type' => 'varchar(15)',
            'label' => '供应商ID',
             'in_list'         => true,
            'default_in_list' => true,
        ),
        'vendor_code' => array(
            'type' => 'varchar(20)',
            'label' => '供应商编码',
             'in_list'         => true,
            'default_in_list' => true,
        ),
        'vendor_name' => array(
            'type' => 'varchar(50)',
            'label' => '供应商名称',
        ),
        'schedule_id' => array(
            'type' => 'varchar(20)',
            'label' => '采购档期',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'schedule_name' => array(
           'type' => 'varchar(30)',
            'label' => 'PO编号',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'po_no' => array(
            'type' => 'varchar(30)',
            'label' => 'PO编号',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'datasign' => array(
            'type' => 'tinyint(2)',
            'label' => '数据标识',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'order_num' => array(
            'type' => 'varchar(80)',
            'label' => 'SO号',
            'comment' => 'itemNo',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'order_date'=>array(
            'type' => 'time',
            'label' => 'SO单的下单日期',
        ),

        'item_no'=>array(
             'type' => 'varchar(30)',
            'label' => '商品条形码',
            'comment' => 'itemNo',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'item_description'=>array(
            'type' => 'varchar(200)',
            'label' => '商品名称',
            'comment' => 'itemDescription',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'goods_no'=>array(
            'type' => 'varchar(200)',
            'label' => '货号',
            'comment' => 'goods_no',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'order_price'=>array(
            'type' => 'decimal(20,8)',
            'label' => 'B2C售卖价',
            'comment' => 'order_price',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'bill_amount'=>array(
            'label' => '可付，结算不含税金额',
            'type' => 'decimal(20,8)',
            'in_list'         => true,
            'default_in_list' => true,
        ),

        'total_bill_amount'=>array(
            'label' => '可付，结算含税金额',
            'type' => 'decimal(20,8)',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'final_total_amount'=>array(
            'label' => '最终可付，结算含税金额(datasign*total_bill_amount)',
            'type' => 'decimal(20,8)',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'tax_rate'=>array(
             'label' => '税率',
             'type' => 'decimal(20,8)',
           
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'signtime'  =>array(
            'type' => 'time',
            'label' => '账单签发时间',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'payable_bill_amount'=>array(
            'label' => '可付，结算含税金额',
            'type' => 'decimal(20,8)',
        ),
        'is_deleted'=>array(
             'label' => '软删除标识（0:未删除,1:已删除）',
             'type' => 'tinyint(1)',
        ),
        'po_price'=>array(
            'label' => 'PO不含税单价',
            'type' => 'decimal(20,8)',
        ),
        'po_tax_price'=>array(
            'label' => 'PO含税单价',
            'type' => 'decimal(20,8)',
        ),
        'payable_total_bill_amount'=>array(
            'label' => '应付，结算含税金额',
            'type' => 'decimal(20,8)',
        ),
        'detail_line_type'=>array(
            'label' => '行类型',
            'type' => 'varchar(30)',
            
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
            
            'filtertype' => 'normal',
            'filterdefault' => true,
        ),
        'active_type'=>array(
            'label' => '活动类型',
            'type' => 'varchar(30)',
        ),
        'active_type_name'=>array(
            'label' => '活动类型名称',
            'type' => 'varchar(100)',
        ),
        'act_parent_no'=>array(
            'label' => '主活动号',
            'type' => 'varchar(100)',
        ),
        'act_parent_name'=>array(
            'label' => '主活动名称',
            'type' => 'varchar(100)',
        ),
        'red_packet_value'=>array(
            'type' => 'time',
            'label' => '活动开始时间',
        ),
        'fav_price'=>array(
            'type' => 'decimal(20,8)',
            'label' => '优惠单价,来源于订单',
        ),
        'total_amount'=>array(
            'type' => 'decimal(20,8)',
            'label' => '优惠总金额,含税',
        ),
        'vendor_red_packet_count'=>array(
            'type' => 'money',
            'label' => '供应商承担数量(',
        ),
        'enter_total_bill_amount'=>array(
            'type' => 'decimal(20,8)',
            'label'=>'供应商承担金额',
        ),
        'enter_payable_total_bill_amount'=>array(
            'type' => 'decimal(20,8)',
            'label'=>'应付,供应商承担金额',
        ),
        'act_vendor_amount'=>array(

            'type' => 'decimal(20,8)',
            'label'=>'供应商承担金额',
        ),
        'new_act_vendor_amount'=>array(
            'type' => 'decimal(20,8)',
            'label'=>'新承担金额',
        ),
      
      
        'po_id' => array(
            'type' => 'int unsigned',
            'label' => '唯品会po_id',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'at_time'        => [
            'type'    => 'TIMESTAMP',
            'label'   => '创建时间',
            'default' => 'CURRENT_TIMESTAMP',
            'width'   => 120,
            'in_list' => false,
            'order'   => 11,
        ],
        'up_time'        => [
            'type'    => 'TIMESTAMP',
            'label'   => '更新时间',
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'width'   => 120,
            'in_list' => false,
            'order'   => 11,
        ],
        
        
        
    ),
    'index'   => array(
        'ind_global_id' => array('columns' => array('global_id'),'prefix'=>'unique'),
        'ind_bill_id' => array('columns' => array('bill_id')),
       
        'ind_detail_line_type' => array('columns' => array('detail_line_type')),
        
    ),
    'comment' => '唯品会账单满减明细',
    'engine' => 'innodb',
    'version' => '$Rev: 40654 $',
);