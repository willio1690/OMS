<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['store_items'] = array(
    'engine'  => 'innodb',
    'version' => '$Rev: $',
    'comment' => app::get('tbo2o')->_('淘宝门店关联商品表'),
    'columns' => array(
        'id' => array(
            'type' => 'varchar(32)',
            'required' => true,
            'pkey' => true,
            'label' => app::get('tbo2o')->_('ID'),
        ),
        'item_iid' => array(
            'type' => 'varchar(50)',
            'required' => false,
            'in_list' => true,
            'label' => app::get('tbo2o')->_('商品IID'),
        ),
        'item_bn' => array(
            'type' => 'bn',
            'required' => true,
            'in_list' => true,
            'default_in_list' => true,
            'filtertype' => true,
            'order' => 10,
            'searchtype' => 'nequal',
            'label' => app::get('tbo2o')->_('宝贝编码'),
        ),
        'item_name' => array(
            'type' => 'varchar(80)',
            'required' => true,
            'searchtype' => 'has',
            'in_list' => true,
            'default_in_list' => true,
            'filterdefault' => true,
            'filtertype' => true,
            'additional' => true,
            'order' => 20,
            'label' => app::get('tbo2o')->_('宝贝名称'),
        ),
        'store_id' => array(
            'type' => 'int unsigned',
            'default' => 0,
            'in_list' => false,
            'label' => app::get('tbo2o')->_('关联门店'),
        ),
        'store_bn' => array (
            'type' => 'varchar(20)',
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 30,
            'label' => app::get('tbo2o')->_('门店编码'),
        ),
        'store_name' => array (
            'type' => 'varchar(80)',
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 40,
            'label' => app::get('tbo2o')->_('门店名称'),
        ),
        'is_bind' => array(
            'type' => 'tinyint(1)',
            'default' => 0,
            'in_list' => false,
            'default_in_list' => true,
            'order' => 80,
            'label' => app::get('tbo2o')->_('绑定状态'),
        ),
        'bind_time' => array(
            'type' => 'time',
            'default' => 0,
            'in_list' => false,
            'order' => 90,
            'label' => app::get('tbo2o')->_('绑定时间'),
        ),
    ),
    'index' => array(
        'ind_item_bn' => array(
            'columns' => array('item_bn'),
        ),
        'ind_store_bn' => array(
            'columns' => array('store_bn'),
        ),
    ),
);