<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class material_finder_extend_filter_sales_material{
    function get_extend_colums(){
        //获取销售物料类型
        $salesMLib = kernel::single('material_sales_material');
        $typeList = $salesMLib->getSalesMaterialTypes();
        
        //扩展字段
        $db['sales_material']=array (
            'columns' => array (
                'sales_material_type' => array(
                    'type' => $typeList,
                    'label' => '销售物料类型',
                    'width' => 120,
                    'editable' => false,
                    'default' => 1,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                ),
                'brand_id' => array (
                    'type' => 'table:brand@ome',
                    'editable' => false,
                    'label' => '品牌',
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                ),
                'fuzzy_sales_material_bn' => array (
                    'type' => 'varchar(200)',
                    'label' => '销售物料编码(模糊)',
                    'width' => 100,
                    'editable' => false,
                    'default' => 1,
                    'in_list' => true,
                    'default_in_list' => true,
                    'filtertype' => 'textarea',
                    'filterdefault' => true,
                ),
                'pz_basic_material_bn' => array (
                    'type' => 'varchar(50)',
                    'label' => '基础物料编码(普通/组合)',
                    'width' => 120,
                    'editable' => false,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                ),
            )
        );
        
        return $db;
    }
}
