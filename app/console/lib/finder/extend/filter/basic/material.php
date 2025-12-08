<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Vsersion: 2022/8/31
 * @Describe: 总库存列表增加额外高级筛选条件
 */
class console_finder_extend_filter_basic_material
{
    function get_extend_colums()
    {
        $list = app::get('ome')->model('brand')->getList('brand_name,brand_id');
        $list = array_column($list, 'brand_name', 'brand_id');
        
        $db['basic_material'] = array(
            'columns' => array(
                'brand_id' => array(
                    'type'            => $list,
                    'label'           => '品牌',
                    'editable'        => false,
                    'in_list'         => false,
                    'default_in_list' => false,
                    'filtertype'      => 'fuzzy_search_multiple',
                    'filterdefault'   => true,
                ),
                'create_time' => array(
                    'type' => 'time',
                    'label' => '创建时间',
                    'in_list' => true,
                    'default_in_list' => true,
                    'default' => 0,
                    'filtertype' => 'time',
                    'filterdefault' => true,
                ),
                'last_modified' => array(
                    'label' => '最后更新时间',
                    'type' => 'time',
                    'width' => 130,
                    'in_list' => true,
                    'default_in_list' => true,
                    'filtertype' => 'time',
                    'filterdefault' => true,
                ),
            )
        );
        return $db;
    }
}