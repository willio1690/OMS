<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class dealer_ctl_admin_shop_stocksync extends inventorydepth_ctl_shop {

    /**
     * index
     * @return mixed 返回值
     */
    public function index()
    {
        $base_filter = array('filter_sql' => '{table}node_id is not null and {table}node_id !=""', 's_type' => 1, 'delivery_mode'=>'shopyjdf');
        list($rs, $cosId) = kernel::single('organization_cos')->getCosList();
        if(!$rs) {
            die('need cos id');
        }
        $base_filter['cos_id'] = $cosId;
        $params = array(
            'title'               => '代发库存同步管理',
            'actions'             => array(),
            //'finder_cols' => 'shop_bn,name,last_store_sync_time',
            'use_buildin_recycle' => false,
            'base_filter'         => $base_filter,
        );

        $this->finder('dealer_mdl_shop_stocksync', $params);
    }
}