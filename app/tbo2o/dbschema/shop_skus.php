<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['shop_skus'] = array(
    'engine'  => 'innodb',
    'version' => '$Rev: $',
    'comment' => app::get('tbo2o')->_('淘宝前端宝贝表'),
    'columns' =>
    array(
        'id' => array(
            'type' => 'varchar(32)',
            'required' => true,
            'pkey' => true,
            'label' => app::get('tbo2o')->_('ID'),
        ),
        'shop_id' => array(
            'type' => 'varchar(32)',
            'required' => true,
            'filterdefault' => true,
            'filtertype' => true,
            'label' => app::get('tbo2o')->_('前端店铺'),
        ),
        'shop_bn' => array(
            'type' => 'varchar(150)',
            'required' => true,
            'filterdefault' => true,
            'filtertype' => true,
            'label' => app::get('tbo2o')->_('店铺编码'),
        ),
       'shop_sku_id'   => array(
            'type' => 'varchar(50)',
            'required' => false,
            'in_list' => true,
            'order' => 20,
            'label' => app::get('tbo2o')->_('货品ID'),
       ),
       'shop_iid' => array(
            'type' => 'varchar(50)',
            'required' => false,
            'in_list' => true,
            'order' => 15,
            'label' => app::get('tbo2o')->_('宝贝ID'),
       ),
       'shop_product_bn' => array(
            'type' => 'varchar(50)',
            'required' => false,
            'in_list' => true,
            'default_in_list' => true,
            'filtertype' => true,
            'order' => 25,
            'searchtype' => 'nequal',
            'label' => app::get('tbo2o')->_('货品编码'),
        ),
        'shop_product_bn_crc32' => array(
            'type' => 'bigint(20)',
            'required' => true,
            'default' => 0,
        ),
        'shop_title' => array(
            'type' => 'varchar(80)',
            'required' => true,
            'searchtype' => 'has',
            'in_list' => true,
            'default_in_list' => true,
            'filterdefault' => true,
            'filtertype' => true,
            'additional' => true,
            'order' => 30,
            'label' => app::get('tbo2o')->_('货品名称'),
        ),
        'shop_price' => array(
            'type' => 'money',
            'default' => 0,
            'in_list' => true,
            'order' => 40,
            'label' => app::get('tbo2o')->_('销售价'),
        ),
        'update_time' => array(
            'type'     => 'last_modify',
            'required' => false,
            'in_list' => false,
            'default' => 0,
            'label'    => app::get('tbo2o')->_('最后更新时间'),
        ),
        'download_time' => array(
            'type' => 'time',
            'default' => 0,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 99,
            'label' => app::get('tbo2o')->_('同步时间'),
        ),
        'product_id' =>array(
            'type' => 'int unsigned',
            'width' => 110,
            'hidden' => true,
            'editable' => false,
            'default' => 0,
            'label' => app::get('tbo2o')->_('绑定后端商品ID'),
        ),
        'product_bn' =>array (
            'type' => 'varchar(200)',
            'width' => 120,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'searchtype' => 'nequal',
            'filtertype' => 'normal',
            'filterdefault' => true,
            'order' => 60,
            'label' => app::get('tbo2o')->_('后端商品编码'),
        ),
        'product_name' =>array (
            'type' => 'varchar(200)',
            'is_title' => true,
            'default_in_list' => true,
            'width' => 260,
            'searchtype' => 'has',
            'editable' => false,
            'filtertype' => 'normal',
            'filterdefault' => true,
            'in_list' => true,
            'order' => 70,
            'label' => app::get('tbo2o')->_('后端商品名称'),
        ),
        'is_bind_product' => array(
            'type' => 'tinyint(1)',
            'default' => 0,
            'in_list' => false,
            'label' => app::get('tbo2o')->_('已绑定后端商品'),
        ),
        'is_bind' => array(
            'type' => 'tinyint(1)',
            'default' => 0,
            'in_list' => false,
            'default_in_list' => true,
            'label' => app::get('tbo2o')->_('绑定状态'),
        ),
        'bind_time' => array(
            'type' => 'time',
            'in_list' => false,
            'default' => 0,
            'label' => app::get('tbo2o')->_('绑定时间'),
        ),
    ),
    'index' => array(
        'idx_shop_sku_id' => array(
            'columns' => array('shop_sku_id'),
        ),
        'ind_shop_product_bn' => array(
            'columns' => array('shop_product_bn'),
        ),
        'ind_product_id' => array(
            'columns' => array('product_id'),
        ),
        'ind_product_bn' => array(
            'columns' => array('product_bn'),
        ),
    ),
);