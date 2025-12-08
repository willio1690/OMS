<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_finder_extend_filter_store
{

    /**
     * 获取_extend_colums
     * @return mixed 返回结果
     */
    public function get_extend_colums()
    {
        // 获取经销商列表，key是bs_bn，value是[bs_bn]name格式
        $dealerList = app::get('dealer')->model('business')->getList('bs_bn,name');
        $dealerList = array_column($dealerList, 'name', 'bs_bn');
        
        // 格式化经销商列表，添加bs_bn前缀
        $formattedDealerList = array();
        foreach ($dealerList as $bs_bn => $name) {
            $formattedDealerList[$bs_bn] = '[' . $bs_bn . ']' . $name;
        }

        $db['store'] = array(
            'columns' => array(
                'dealer_bs_bn'    => array(
                    'type'          => $formattedDealerList,
                    'label'         => '经销商',
                    'filtertype'    => 'fuzzy_search',
                    'filterdefault' => true,
                    'panel_id'      => 'store_finder_top',
                    'order'         => 1,
                ),
            ),
        );

        return $db;
    }

}
