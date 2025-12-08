<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['shop_products'] = array(
    'engine'  => 'innodb',
    'version' => '$Rev: $',
    'comment' => app::get('tbo2o')->_('淘宝后端货品表'),
    'columns' => array(
        'id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'hidden' => true,
            'editable' => false,
            'label' => app::get('tbo2o')->_('ID'),
        ),
        'bn' => array (
            'type' => 'varchar(50)',
            'width' => 120,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'required' => true,
            'searchtype' => 'nequal',
            'filtertype' => 'normal',
            'filterdefault' => true,
            'order' => 10,
            'label' => app::get('tbo2o')->_('后端商品编码'),
        ),
        'name' => array (
            'type' => 'varchar(80)',
            'required' => true,
            'is_title' => true,
            'default_in_list' => true,
            'width' => 260,
            'searchtype' => 'has',
            'editable' => false,
            'filtertype' => 'normal',
            'filterdefault' => true,
            'in_list' => true,
            'order' => 15,
            'label' => app::get('tbo2o')->_('后端商品名称'),
        ),
        'barcode' => array (
            'type' => 'varchar(50)',
            'editable' => false,
            'required' => true,
            'hidden' => true,
            'in_list' => true,
            'default_in_list' => false,
            'order' => 20,
            'label' => app::get('tbo2o')->_('条形码'),
        ),
        'type' => array (
            'type' => 'tinyint(1)',
            'width' => 100,
            'editable' => false,
            'default' => 0,
            'label' => app::get('tbo2o')->_('商品类型'),
        ),
        'visibled' => array(
            'type' => 'tinyint(1)',
            'editable' => false,
            'default' => 1,
            'label' => app::get('tbo2o')->_('销售状态'),
        ),
        'create_time' => array(
            'type' => 'time',
            'in_list' => true,
            'default' => 0,
            'default_in_list' => true,
            'order' => 80,
            'label' => app::get('tbo2o')->_('创建时间'),
        ),
        'outer_id' => array (
            'type' => 'varchar(30)',
            'width' => 110,
            'in_list' => true,
            'editable' => false,
            'order' => 50,
            'label' => app::get('tbo2o')->_('外部商品ID'),
        ),
        'sync_time' => array(
            'type' => 'time',
            'default' => 0,
            'order' => 65,
            'label' => app::get('tbo2o')->_('同步时间'),
        ),
       'is_sync' => array(
            'type' => 'tinyint(1)',
            'default' => 0,
            'order' => 60,
            'label' => app::get('tbo2o')->_('同步状态'),
        ),
    ),
    'index' => array(
        'ind_bn' => array(
            'columns' => array('bn'),
        ),
    ),
);