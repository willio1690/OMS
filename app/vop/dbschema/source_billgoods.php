<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['source_billgoods']=array (
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
        'vendor_name'=>array(
            'type' => 'varchar(50)',
            'label' => '供应商名称',
        ),

        'schedule_id'=>array(
            'type' => 'varchar(20)',
            'label' => '采购档期',
            'in_list'         => true,
            'default_in_list' => true,
        ),

        'schedule_name'=>array(
            'type' => 'varchar(30)',
            'label' => '采购档期',
             'in_list'         => true,
            'default_in_list' => true,
        ),
        'order_num'=>array(
            'type' => 'varchar(32)',
            'label' => 'SO号',
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
        'tax_rate'=>array(
            'label' => '税率',
             'type' => 'decimal(20,8)',
           
            'in_list'         => true,
            'default_in_list' => true,
            
        ),
        'po_no' => array(
            'type' => 'varchar(30)',
            'label' => 'PO编号',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'po_createtime'=>array(


             'type' => 'time',
            'label' => 'SO单的下单日期',
        ),
        'is_deleted'=>array(
            'label' => '软删除标识（0:未删除,1:已删除）',
             'type' => 'tinyint(1)',
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
        'datasign' => array(
            'type' => 'tinyint(2)',
            'label' => '数据标识',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'bill_price'=>array(
            'label' => '结算不含税单价',
            'type' => 'decimal(20,8)',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'bill_tax_price'=>array(
            'label' => '结算含税单价',
            'type' => 'decimal(20,8)',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'payable_quantity'=>array(
            'label' => '应付，结算数量',
            'type' => 'money',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'bill_quantity'=>array(
            'label' => '可付，结算数量',
            'type' => 'money',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'bill_amount'=>array(
            'label' => '可付，结算不含税金额',
            'type' => 'decimal(20,8)',
            'in_list'         => true,
            'default_in_list' => true,
        ),

        'total_bill_amount'=>array(
            'label' => '可付，结算含税金额',
            'type' => 'decimal(20,2)',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'final_total_amount'=>array(
            'label' => '最终可付，结算含税金额(datasign*total_bill_amount)',
            'type' => 'decimal(20,2)',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'reference_number'=>array(
            'label' => '参考号-换货的原单号',
            'type' => 'varchar(32)',
            'in_list'         => true,
            'default_in_list' => true,
        ),

        'exchange_flag'=>array(
            'label' => '换货标记 Y（是） N（否)',
            'type'     => 'tinybool',
            'default'  => 'N',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'source_line_type'=>array(
            'label' => '来源行类型',
            'type' => 'varchar(30)',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'signtime'  =>array(
            'type' => 'time',
            'label' => '账单签发时间',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'pick_no'=>array(
            'label' => '拣货单号',
            'type' => 'varchar(32)',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'delivery_no'=>array(
            'label' => '送货单号',
            'type' => 'varchar(32)',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'source_line_name'=>array(
            'label' => '来源行类型名称',
            'type' => 'varchar(50)',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'detail_line_name'=>array(
            'label' => '行类型名称',
            'type' => 'varchar(50)',
            'in_list'         => true,
            'default_in_list' => true,
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
        
        'po_id' => array(
            'type' => 'int unsigned',
            'label' => '唯品会po_id',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
       
    ),
    'index'   => array(
        'ind_global_id' => array('columns' => array('global_id'),'prefix'=>'unique'),
        'ind_bill_id' => array('columns' => array('bill_id')),

        'ind_detail_line_type' => array('columns' => array('detail_line_type')),
        'ind_detail_line_name' => array('columns' => array('detail_line_name')),
    ),
    'comment' => '唯品会账单明细',
    'engine' => 'innodb',
    'version' => '$Rev: 40654 $',
);