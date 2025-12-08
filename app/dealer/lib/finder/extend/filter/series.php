<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class dealer_finder_extend_filter_series
{
    /**
     * 获取_extend_colums
     * @return mixed 返回结果
     */
    public function get_extend_colums()
    {
        $endorseMdl = app::get('dealer')->model('series_endorse');
        $shopMdl    = app::get('ome')->model('shop');

        $seriesShop = $endorseMdl->getList('*');
        $seriesShop = array_unique(array_column($seriesShop, 'shop_id'));
        $shopList   = $shopMdl->getList('shop_id,shop_bn,name', ['delivery_mode' => 'shopyjdf', 'shop_id|in' => $seriesShop]);
        $shopList   = array_column($shopList, 'name', 'shop_id');

        $db['series'] = array(
            'columns' => array(
                'series_shop' => array(
                    'type'          => $shopList,
                    'label'         => '授权经销店铺',
                    'filtertype'    => 'fuzzy_search',
                    // 'filtertype'    => 'fuzzy_search_multiple', // 多选
                    'filterdefault' => true,
                ),
            ),
        );

        return $db;
    }
}
