<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class dealer_finder_extend_filter_goods_price
{
    /**
     * 获取_extend_colums
     * @return mixed 返回结果
     */
    public function get_extend_colums()
    {
        // 获取经销商列表
        $dealerMdl = app::get('dealer')->model('business');
        $dealerList = $dealerMdl->getList('bs_id,bs_bn,name', array('status' => 'active'));
        
        $dealerOptions = array();
        foreach ($dealerList as $dealer) {
            $dealerOptions[$dealer['bs_id']] = $dealer['bs_bn'] . ' - ' . $dealer['name'];
        }

        $db['goods_price'] = array(
            'columns' => array(
                'bs_id' => array(
                    'type'          => $dealerOptions,
                    'label'         => '经销商',
                    'filtertype'    => 'fuzzy_search_multiple',
                    'filterdefault' => true,
                ),
                'material_bn' => array(
                    'type'          => 'text',
                    'label'         => '基础物料编码',
                    'filtertype'    => 'normal',
                    'filterdefault' => true,
                ),
            ),
        );

        return $db;
    }
}
