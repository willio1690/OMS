<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_finder_extend_filter_inventory
{

    /**
     * 获取_extend_colums
     * @return mixed 返回结果
     */
    public function get_extend_colums()
    {
        $storeList = app::get('o2o')->model('store')->getList('store_bn,name');
        $storeList = array_column($storeList, 'name', 'store_bn');

        $db['inventory'] = array(
            'columns' => array(
                'physics_id'    => array(
                    'type'          => $storeList,
                    'label'         => '门店',
                    'filtertype'    => 'fuzzy_search',
                    'filterdefault' => true,
                    'panel_id'      => 'inventory_finder_top',
                    'order'         => 1,

                ),
            ),
        );

        return $db;
    }

}
