<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['orders'] = array(
    'columns' => array(
        'id'           => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'editable' => false,
            'extra'    => 'auto_increment',
        ),
        'tid'           => array(
            'type'            => 'varchar(32)',
            'required'        => true,
            'label'           => '交易编号',
            'is_title'        => true,
            'width'           => 180,
            'searchtype'      => 'nequal',
            'editable'        => false,
            'filtertype'      => 'textarea',
            'filterdefault'   => true,
            'in_list'         => true,
            'default_in_list' => false,
            'order'           => '2',
        ),
       
      
        'store_bn'           => array(
            'type'            => 'varchar(32)',
            
            'label'           => '下单门店编码',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => '3',
        ),
        'title'           => array(
            'type'            => 'varchar(32)',
            
            'label'           => '交易标题',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => '4',
        ),
        'created'           => array(
            'type'            => 'time',
            
            'label'           => '交易创建时间',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => '5',
        ),
        'modified'           => array(
            'type'            => 'time',
            
            'label'           => '交易最后更新时间',
            'in_list'         => true,
            'default_in_list' => true,
        ),

        'status'           => array(
            'type'            => 'varchar(32)',
            
            'label'           => '交易状态',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'pay_status'           => array(
            'type'            => 'varchar(32)',
            
            'label'           => '交易支付状态',
            'in_list'         => true,
            'default_in_list' => true,
          
        ),
        'ship_status'           => array(
            'type'            => 'varchar(32)',
            
            'label'           => '交易物流状态',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        
        
        'total_goods_fee'           => array(
          
            'label'           => '商品总额',
            'type'            => 'money',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
           
            'width'           => '70',
        ),
        'total_trade_fee'           => array(
            'label'           => '交易金额',
            'type'            => 'money',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'discount_fee'           => array(
            'label'           => '折扣优惠金额',
            'type'            => 'money',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'goods_discount_fee'  => array(
            
            'label'           => '商品优惠金额',
            'type'            => 'money',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'orders_discount_fee'=> array(
            
            
            'label'           => '订单优惠金额',
            'type'            => 'money',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
        ),
       
        'payed_fee'=> array(
            'label'           => '已支付金额',
            'type'            => 'money',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
        ),
      
        
        'tradetype'  =>array(
            'label'           => '交易类型',
            'type'            => 'varchar(32)',
        ),
        'orders_number'  =>array(
            'label'    => '子订单数量',
            'type'     => 'number',
        ),
      
        'at_time'       => array(
            'type'    => 'TIMESTAMP',
            'label'   => '创建时间',
            'default' => 'CURRENT_TIMESTAMP',
            'width'   => 120,
            'in_list' => false,
            'order'   => 11,
        ),
        'up_time'       => array(
            'type'    => 'TIMESTAMP',
            'label'   => '更新时间',
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'width'   => 120,
            'in_list' => false,
            'order'   => 11,
        ),
        'params'=>array(

            'type'     => 'longtext',

            'label'    => '原始请求参数',
        ),
        'source'           => array(
            'type'            => 'varchar(32)',
            'label'           => '来源',
            'in_list'         => true,
            'default_in_list' => true,
        ),

    ),
    'index'   => array(
        'ind_order_bn_store'     => array('columns' => array(0 => 'tid', 1 => 'store_bn'), 'prefix' => 'unique'),

    ),
    'comment' => '订单表',
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
