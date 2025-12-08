<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['setting']=array (
    'columns' => array (
        'sid' =>
            array (
              'type' => 'number',
              'required' => true,
              'pkey' => true,
              'extra' => 'auto_increment',
              'editable' => false,
              'order' => 1,
        ),
        'is_merge' =>
            array (
                    'type' => 'tinyint(1)',
                    'label' => '同入库仓合并',
                    'editable' => false,
                    'default' => 0,
                    'order' => 10,
                    'in_list' => true,
            ),
        'is_auto_combine' =>
            array (
                    'type' => 'tinyint(1)',
                    'label' => '启用自动审核',
                    'editable' => false,
                    'default' => 0,
                    'order' => 20,
                    'in_list' => true,
            ),
        'branch_id' =>
            array (
                    'type' => 'table:branch@ome',
                    'label' => '出库仓',
                    'editable' => false,
                    'required' => false,
                    'order' => 40,
                    'in_list' => true,
                    'default_in_list' => false,
            ),
        'exec_hour' =>
            array (
                    'type' => 'varchar(80)',
                    'label' => '每日审核时间点',
                    'required' => false,
                    'editable' => false,
                    'order' => 50,
            ),
        'carrier_code' =>
            array (
                    'type' => 'varchar(30)',
                    'label' => '承运商',
                    'required' => false,
                    'editable' => false,
                    'order' => 60,
                    'in_list' => true,
                    'default_in_list' => false,
        ),
        'dly_mode' =>
            array (
                    'type' => 'varchar(30)',
                    'label' => '配送方式',
                    'required' => false,
                    'editable' => false,
                    'order' => 70,
                    'in_list' => true,
                    'default_in_list' => false,
            ),
        'arrival_type' =>
            array (
                    'type' => 'varchar(30)',
                    'label' => '审单时间类型',
                    'required' => false,
                    'editable' => false,
                    'order' => 80,
            ),
        'arrival_day' =>
            array (
                    'type' => 'tinyint(3)',
                    'label' => '时间天数',
                    'editable' => false,
                    'default' => 0,
                    'order' => 90,
            ),
        'arrival_hour' =>
            array (
                    'type' => 'varchar(30)',
                    'label' => '时间段',
                    'required' => false,
                    'editable' => false,
                    'order' => 95,
            ),
        'create_time' =>
            array (
                    'type' => 'time',
                    'label' => '创建时间',
                    'default' => 0,
                    'in_list' => true,
                    'default_in_list' => true,
                    'width' => 130,
                    'editable' => false,
                    'filtertype' => 'time',
                    'filterdefault' => true,
                    'order' => 98,
            ),
    ),
    'index' => array (
            
    ),
    'comment' => '自动审核配置',
    'engine' => 'innodb',
    'version' => '$Rev: $',
);