<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['pick_bill_items']=array (
    'columns' => array (
        'bill_item_id' =>
            array (
              'type' => 'int unsigned',
              'required' => true,
              'pkey' => true,
              'extra' => 'auto_increment',
              'editable' => false,
        ),
        'bill_id' =>
            array (
              'type' => 'number',
              'required' => true,
              'default' => 0,
              'label' => '拣货单编号',
              'editable' => false,
        ),
        'bn' =>
            array (
                    'type' => 'varchar(30)',
                    'required' => false,
                    'label' => '货号',
                    'width' => 100,
                    'editable' => false,
            ),
        'product_name' =>
            array (
                    'type' => 'varchar(80)',
                    'label' => '商品名称',
                    'width' => 130,
                    'editable' => false,
            ),
        'barcode' =>
            array (
                    'type' => 'varchar(80)',
                    'required' => false,
                    'label' => '条码',
                    'width' => 100,
                    'editable' => false,
            ),
        'size' =>
            array (
                    'type' => 'varchar(32)',
                    'label' => '尺寸',
                    'width' => 80,
                    'editable' => false,
                    'in_list' => true,
            ),
        'num' =>
            array (
                    'type' => 'number',
                    'label' => '拣货数量',
                    'editable' => false,
                    'required' => false,
                    'default' => 0,
            ),
        'not_delivery_num' =>
            array (
                    'type' => 'number',
                    'label' => '未送货数量',
                    'editable' => false,
                    'required' => false,
                    'default' => 0,
            ),
        'price' =>
            array (
                    'type' => 'money',
                    'label' => '供货价(不含税)',
                    'default' => 0,
                    'width' => 60,
					'editable' => false,
            ),
        'market_price' =>
            array (
                    'type' => 'money',
                    'label' => '供货价(含税)',
                    'default' => 0,
                    'width' => 60,
					'editable' => false,
            ),
            'product_id' =>
            array (
                'type' => 'number',
             
                'editable' => false,
            ),
            'branch_id'=>array(
                'type' => 'number',
                'default'=>0,
                'editable' => false,
                'label' => '首选仓库',
                'width' => 110,
              
            ),
            'goods_type_map'=>array(
                'type' => 'text',
                'label' => '商品类型映射',
            ),
            'quality_check_flag'=>array(
                'type' => 'int',
                'label' => '是否换货质检',
            ),
            'security_type'=>array(
                'type' => 'int',
                'label' => '防伪码管控',
            ),
            'at_time'           => [
                'type'    => 'TIMESTAMP',
                'label'   => '创建时间',
                'default' => 'CURRENT_TIMESTAMP',
                'width'   => 120,
                'in_list' => true,
            ],
            'up_time'           => [
                'type'    => 'TIMESTAMP',
                'label'   => '更新时间',
                'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                'width'   => 120,
                'in_list' => true,
            ],
    ),
    'index' => array (
        'ind_bill_id' =>
            array (
            'columns' =>
                  array (
                    0 => 'bill_id',
                  ),
        ),
        'ind_at_time' => ['columns' => ['at_time']],
        'ind_up_time' => ['columns' => ['up_time']],
    ),
    'comment' => '拣货单明细',
    'engine' => 'innodb',
    'version' => '$Rev: $',
);