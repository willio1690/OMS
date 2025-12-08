<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 基础物料扩展数据结构
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */

$db['basic_material_ext']=array(
    'columns' =>
        array(
            'bm_id' =>
                array(
                    'type' => 'int unsigned',
                    'required' => true,
                    'width' => 110,
                    'hidden' => true,
                    'editable' => false,
                    'pkey' => true,
                    'comment' => '基础物料ID,关联material_basic_material.bm_id'
                ),
            'cost' =>
                array(
                    'type' => 'money',
                    'default' => '0.000',
                    'label' => '成本价',
                    'width' => 110,
                    'comment'=>'基础物料固定成本价',
                ),
            'retail_price' =>
                array(
                    'type' => 'money',
                    'default' => '0.000',
                    'label' => '零售价',
                    'width' => 75,
                    'comment'=>'基础物料市场零售价',
                ),
            'purchasing_price' =>
                array(
                    'type' => 'money',
                    'default' => '0.000',
                    'label' => '采购进价',
                    'width' => 110,
                    'comment'=>'基础物料采购进价',
                ),
            'weight' =>
                array(
                    'type' => 'decimal(20,3)',
                    'label' => '重量',
                    'default' => '0.000',
                    'width' => 110,
                    'editable' => false,
                    'comment' => '基础物料重量,单位:g',
                ),
            'length' =>
                array(
                    'type' => 'decimal(20,3)',
                    'label' => '长度',
                    'default' => '0.000',
                    'width' => 110,
                    'editable' => false,
                ),
            'width' =>
                array(
                    'type' => 'decimal(20,3)',
                    'label' => '宽度',
                    'default' => '0.000',
                    'width' => 110,
                    'editable' => false,
                ),
            'high' =>
                array(
                    'type' => 'decimal(20,3)',
                    'label' => '高度',
                    'default' => '0.000',
                    'width' => 110,
                    'editable' => false,
                ),
            'volume' =>
                array(
                    'type' => 'decimal(20,3)',
                    'label' => '体积',
                    'default' => '0.000',
                    'width' => 110,
                    'editable' => false,
                ),
            'unit' =>
                array(
                    'type' => 'varchar(20)',
                    'label' => '包装单位',
                    'width' => 100,
                    'editable' => false,
                ),
            'specifications' =>
                array(
                    'type' => 'varchar(255)',
                    'label' => '物料规格',
                    'width' => 100,
                    'editable' => false,
                ),
            'brand_id' =>
                array(
                    'type' => 'number',
                    'label' => '品牌ID',
                    'width' => 150,
                    'editable' => false,
                    'comment' => '品牌ID,关联ome_brand.brand_id'
                ),
            'cat_id' =>
                array(
                    'type' => 'number',
                    'label' => '物料类型ID',
                    'width' => 100,
                    'editable' => false,
                    'comment' => '物料类型ID,关联ome_goods_type.type_id',
                ),
            'banner' =>
                array(
                    'type' => 'text',
                    'label' => '轮播图',
                    'width' => 100,
                    'editable' => false,
                ),
            'color'              => array(
                'type'     => 'varchar(255)',
                'label'    => '颜色',
                'width'    => 100,
                'editable' => false,
            ),
//            'size'               => array(
//                'type'     => 'varchar(255)',
//                'label'    => '尺码',//弃用扩展表尺码字段，使用主表尺码字段
//                'width'    => 100,
//                'editable' => false,
//            ),
            'box_spec' =>
                array(
                    'type' => 'varchar(20)',
                    'label' => '箱规',
                    'width' => 100,
                    'editable' => false,
                ),
             'net_weight' =>
                array(
                    'type' => 'decimal(20,3)',
                    'label' => '重量',
                    'default' => '0.000',
                    'width' => 110,
                    'editable' => false,
                    'comment' => '基础物料净量,单位:g',
                ),
        ),
    'comment' => '基础物料扩展表,用于存储基础物料的额外属性信息',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);
