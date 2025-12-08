<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['order_receiver'] = array(
    'columns' => array(
        'order_id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'default' => 0,
            'editable' => false,
            'pkey' => true,
            'comment' => '订单号',
        ),
        'encrypt_source_data' => array(
            'type' => 'text',
            'default' => '',
            'label' => '加密数据',
            'required' => false,
            'editable' => false,
        ),
        'platform_country_id' => array(
            'type' => 'varchar(30)',
            'editable' => false,
            'label' => '平台国家ID',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'platform_province_id' => array(
            'type' => 'varchar(30)',
            'editable' => false,
            'label' => '平台省ID',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'platform_city_id' => array(
            'type' => 'varchar(30)',
            'editable' => false,
            'label' => '平台市ID',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'platform_district_id' => array(
            'type' => 'varchar(30)',
            'editable' => false,
            'label' => '平台地区ID',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'platform_town_id' => array(
            'type' => 'varchar(30)',
            'editable' => false,
            'label' => '平台镇ID',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'ship_province' => array(
            'type' => 'varchar(30)',
            'editable' => false,
            'label' => '省',
            'comment' => '省',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'ship_city' => array(
            'type' => 'varchar(30)',
            'editable' => false,
            'label' => '市',
            'comment' => '市',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'ship_district' => array(
            'type' => 'varchar(30)',
            'editable' => false,
            'label' => '区',
            'comment' => '区',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'ship_town' => array(
            'type' => 'varchar(30)',
            'editable' => false,
            'label' => '乡镇',
            'comment' => '乡镇',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'ship_village' => array(
            'type' => 'varchar(50)',
            'editable' => false,
            'label' => '村',
            'comment' => '村',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'archive_time' => array(
            'type' => 'int unsigned',
            'label' => '归档时间',
            'width' => 130,
            'editable' => false,
            'in_list' => true,
            'filtertype' => 'time',
            'filterdefault' => true,
        ),
    ),
    'index' => array(
        'ind_order_id' => array(
            'columns' => array(
                0 => 'order_id',
            ),
        ),
        'ind_archive_time' => array(
            'columns' => array(
                0 => 'archive_time',
            ),
        ),
    ),
    'engine' => 'innodb',
    'version' => '$Rev: 40912 $',
    'comment' => '归档订单收货人信息表',
); 