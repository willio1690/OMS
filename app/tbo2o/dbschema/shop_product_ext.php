<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['shop_product_ext'] = array(
    'engine'  => 'innodb',
    'version' => '$Rev: $',
    'comment' => app::get('tbo2o')->_('淘宝后端货品扩展表'),
    'columns' => array(
        'id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'hidden' => true,
            'editable' => false,
            'label' => app::get('tbo2o')->_('ID'),
        ),
        'is_fragile' => array(
            'type' => 'tinyint(1)',
            'width'=> 100,
            'editable'=> false,
            'default' => 0,
            'label'=> app::get('tbo2o')->_('是否易碎品'),
        ),
        'is_dangerous' => array(
            'type' => 'tinyint(1)',
            'width'=> 100,
            'editable'=> false,
            'default' => 0,
            'label'=> app::get('tbo2o')->_('是否危险品'),
        ),
        'is_costly' => array(
            'type' => 'tinyint(1)',
            'width'=> 100,
            'editable'=> false,
            'default' => 0,
            'label'=> app::get('tbo2o')->_('是否贵重品'),
        ),
        'is_warranty' => array(
            'type' => 'tinyint(1)',
            'width'=> 100,
            'editable'=> false,
            'default' => 0,
            'label'=> app::get('tbo2o')->_('是否保质期'),
        ),
        'price' =>
            array (
            'type' => 'money',
            'default' => '0.000',
            'width' => 110,
            'label' => app::get('tbo2o')->_('单价'),
        ),
        'weight' => array (
            'type' => 'number',
            'default' => '0',
            'width' => 110,
            'editable' => false,
            'label' => app::get('tbo2o')->_('重量'),
        ),
        'length' => array (
            'type' => 'varchar(10)',
            'width' => 100,
            'editable' => false,
            'label' => app::get('tbo2o')->_('长'),
        ),
        'width' => array (
            'type' => 'varchar(10)',
            'width' => 100,
            'editable' => false,
            'label' => app::get('tbo2o')->_('宽'),
        ),
        'height' => array (
            'type' => 'varchar(10)',
            'width' => 100,
            'editable' => false,
            'label' => app::get('tbo2o')->_('高'),
        ),
        'volume' => array (
            'type' => 'varchar(10)',
            'width' => 100,
            'editable' => false,
            'label' => app::get('tbo2o')->_('体积'),
        ),
        'matter_status' => array (
            'type' => array (
                '0' => '液体',
                '1' => '粉体',
                '2' => '固体',
            ),
            'width' => 70,
            'editable' => false,
            'filtertype' => 'yes',
            'label' => app::get('tbo2o')->_('材质'),
        ),
        'brand_id' => array(
            'type' => 'number',
            'width' => 150,
            'editable' => false,
            'default' => 0,
            'label' => app::get('tbo2o')->_('品牌'),
        ),
        'brand_name' => array (
            'type' => 'varchar(50)',
            'editable' => false,
            'label' => app::get('tbo2o')->_('品牌名称'),
        ),
        'is_area_sale' => array(
            'type' => 'tinyint(1)',
            'width'=> 100,
            'editable'=> false,
            'default' => 0,
            'label'=> app::get('tbo2o')->_('是否区域销售'),
        ),
        'item_type' => array(
            'type' => 'number',
            'width' => 100,
            'default' => 0,
            'editable' => false,
            'label' => app::get('tbo2o')->_('商品类型'),
        ),
    ),
);