<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['shop_items'] = array(
    'engine'  => 'innodb',
    'version' => '$Rev: $',
    'comment' => app::get('tbo2o')->_('店铺商品表'),
    'columns' => array(
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
            'type' => 'bn',
            'required' => true,
            'in_list' => true,
            'filterdefault' => true,
            'filtertype' => true,
            'label' => app::get('tbo2o')->_('店铺编码'),
        ),
        'shop_name' => array(
            'type' => 'varchar(255)',
            'required' => true,
            'order' => 20,
            'label' => app::get('tbo2o')->_('店铺名称'),
        ),
        'shop_type' => array(
            'type'     => 'varchar(50)',
            'required' => true,
            'default'  => '',
            'hidden'   => true,
            'label'    => app::get('tbo2o')->_('店铺类型'),
        ),
        'iid' => array(
            'type' => 'varchar(50)',
            'required' => false,
            'label' => app::get('tbo2o')->_('店铺商品数字ID'),
        ),
        'bn' => array(
            'type' => 'bn',
            'required' => true,
            'in_list' => true,
            'default_in_list' => true,
            'filtertype' => true,
            'order' => 40,
            'searchtype' => 'nequal',
            'label' => app::get('tbo2o')->_('店铺商品编码'),
        ),
        'title' => array(
            'type' => 'varchar(80)',
            'required' => true,
            'searchtype' => 'has',
            'in_list' => true,
            'default_in_list' => true,
            'filterdefault' => true,
            'filtertype' => true,
            'additional' => true,
            'order' => 50,
            'label' => app::get('tbo2o')->_('店铺商品名称'),
        ),
        'detail_url' => array(
            'type' => 'text',
            'default' => '',
            'in_list' => true,
            'label' => app::get('tbo2o')->_('访问URL'),
        ),
        'frame_set' => array(
            'type' => 'bool',
            'default' => 'true',
            'label' => app::get('tbo2o')->_('上下架'),
        ),
        'approve_status' => array(
            'type' => array(
                'onsale' => app::get('tbo2o')->_('上架'),
                'instock' => app::get('tbo2o')->_('下架'),
            ),
            'default' => 'onsale',
            'panel_id' => 'frame_finder_top',
            'filterdefault' => true,
            'filtertype' => true,
            'order' => 10,
            'label' => app::get('tbo2o')->_('商品在架状态'),
        ),
        'disabled' => array(
            'type' => 'bool',
            'default' => 'false',
        ),
        'price' => array(
            'type' => 'money',
            'default' => 0,
            'in_list' => true,
            'label' => app::get('tbo2o')->_('销售价'),
        ),
        'sku_num' => array(
            'type' => 'mediumint(8)',
            'default' => 0,
            'label' => app::get('tbo2o')->_('SKU数'),
        ),
        'shop_store' => array(
            'type' => 'number',
            'default' => 0,
            'filtertype' => 'normal',
            'panel_id' => 'frame_finder_top',
            'filterdefault' => true,
            'default_in_list' => true,
            'in_list' => true,
            'label' => app::get('tbo2o')->_('店铺库存'),
        ),
        'taog_store' => array(
            'type' => 'number',
            'default' => 0,
            'filtertype' => 'normal',
            'panel_id' => 'frame_finder_top',
            'filterdefault' => true,
            'default_in_list' => true,
            'in_list' => true,
            'label' => app::get('tbo2o')->_('在售库存'),
        ),
        'download_time' => array(
            'type' => 'time',
            'default' => 0,
            'in_list' => true,
            'default_in_list' => true,
            'label' => app::get('tbo2o')->_('同步时间'),
        ),
        'update_time' => array(
            'type' => 'last_modify',
            'required' => false,
            'in_list' => false,
            'default' => 0,
            'label' => app::get('tbo2o')->_('最后更新时间'),
        ),
    ),
    'index' => array(
        'idx_iid' => array(
            'columns' => array('iid'),
        ),
        'ind_bn' => array(
            'columns' => array('bn'),
        ),
    ),
);