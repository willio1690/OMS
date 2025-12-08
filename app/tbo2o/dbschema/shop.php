<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['shop'] = array(
    'columns' => array(
        'id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'label' => app::get('tbo2o')->_('ID'),
        ),
        'company_name' => array(
            'type' => 'varchar(255)',
            'order' => 20,
            'label' => app::get('tbo2o')->_('商户名称'),
        ),
        'company_content' => array(
            'type' => 'varchar(255)',
            'order' => 20,
            'label' => app::get('tbo2o')->_('商户介绍'),
        ),
        'shop_id' => array(
            'type' => 'varchar(32)',
            'filterdefault' => true,
            'filtertype' => true,
            'label' => app::get('tbo2o')->_('前端店铺ID'),
        ),
        'shop_bn' => array(
            'type' => 'bn',
            'in_list' => true,
            'filterdefault' => true,
            'filtertype' => true,
            'label' => app::get('tbo2o')->_('前端店铺编码'),
        ),
        'shop_name' => array(
            'type' => 'varchar(255)',
            'order' => 20,
            'label' => app::get('tbo2o')->_('前端店铺名称'),
        ),
        'branch_bn' =>
        array (
            'type' => 'varchar(32)',
            'label' => '仓库编号',
        ),
        'create_time' => array(
            'type' => 'time',
            'default' => 0,
            'in_list' => true,
            'default_in_list' => true,
            'label' => app::get('tbo2o')->_('创建时间'),
        ),
    ),
    'index' =>
    array (
        'ind_shop_id' => array ( 'columns' => array ( 'shop_id',)),
        'ind_branch_bn' => array ( 'columns' => array ( 'branch_bn',)),
    ),
    'engine'  => 'innodb',
    'version' => '$Rev: $',
    'comment' => app::get('tbo2o')->_('全渠道店铺表'),
);