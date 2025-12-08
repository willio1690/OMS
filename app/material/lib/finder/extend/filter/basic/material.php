<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/*
 * 基础物料加高级筛选字段
 * by wangjianjun 20170811
 */
class material_finder_extend_filter_basic_material{
    function get_extend_colums(){
        //获取物料类型
        $mdl_goods_type = app::get('ome')->model('goods_type');
        $rs_goods_types = $mdl_goods_type->getList('type_id,name');
        $goods_types_list = array();
        foreach ($rs_goods_types as $var_g_t){
            $goods_types_list[$var_g_t["type_id"]] = $var_g_t["name"];
        }
        
        //物料属性
        $materialLib = kernel::single('material_basic_material');
        $materialTypes = $materialLib->get_material_types();
        
        //扩展字段
        $db['basic_material']=array (
            'columns' => array (
                'goods_type' => array (
                    'type' => $goods_types_list,
                    'editable' => false,
                    'label' => '物料类型',
                    'default' => '0',
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                ),
                'type' => array (
                    'type' => $materialTypes,
                    'label' => '物料属性',
                    'width' => 100,
                    'editable' => false,
                    'default' => 1,
                    'in_list' => true,
                    'default_in_list' => true,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                ),
                'fuzzy_material_bn' => array (
                    'type' => 'varchar(200)',
                    'label' => '基础物料编码(模糊)',
                    'width' => 100,
                    'editable' => false,
                    'default' => 1,
                    'in_list' => true,
                    'default_in_list' => true,
                    'filtertype' => 'textarea',
                    'filterdefault' => true,
                ),
            )
        );
        
        return $db;
    }
}
