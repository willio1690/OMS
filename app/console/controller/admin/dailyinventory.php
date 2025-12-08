<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2022/12/7 11:19:23
 * @describe: 控制器
 * ============================
 */
class console_ctl_admin_dailyinventory extends desktop_controller
{
    /**
     * wmsIndex
     * @return mixed 返回值
     */

    public function wmsIndex()
    {
        $actions = array();
        $params  = array(
            'title'               => '日盘单',
            'use_buildin_set_tag' => false,
            'use_buildin_filter'  => true,
            'use_buildin_export'  => false,
            'use_buildin_import'  => false,
            'use_buildin_recycle' => false,
            'actions'             => $actions,
            'orderBy'             => 'id desc',
            'base_filter'         => ['channel_type' => 'wms'],
            'finder_aliasname'    => 'wmsdinv',
        );

        $this->finder('console_mdl_dailyinventory', $params);
    }

    /**
     * wmsItemIndex
     * @param mixed $dlyinv_id ID
     * @return mixed 返回值
     */
    public function wmsItemIndex($dlyinv_id = 0)
    {
        $actions = array(
            array('label' => '返回', 'href' => 'index.php?app=console&ctl=admin_dailyinventory&act=wmsIndex&finder_id=' . $_GET['finder_id']),
        );
        $params = [
            'title'                  => '日盘明细',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'    => false,
            'use_buildin_recycle'    => false,
            'use_buildin_export'     => true,
            'use_buildin_import'     => false,
            'use_buildin_filter'     => true,
            'use_view_tab'           => false,
            'base_filter'            => ['dlyinv_id' => $dlyinv_id],
            'finder_aliasname'       => 'wmsdinv',
            'actions'                => $actions,

        ];

        $this->finder('console_mdl_dailyinventory_items', $params);
    }

    /**
     * storeIndex
     * @return mixed 返回值
     */
    public function storeIndex()
    {
        $actions = array();
        $params  = array(
            'title'               => '日盘单',
            'use_buildin_set_tag' => false,
            'use_buildin_filter'  => true,
            'use_buildin_export'  => false,
            'use_buildin_import'  => false,
            'use_buildin_recycle' => false,
            'actions'             => $actions,
            'orderBy'             => 'id desc',
            'base_filter'         => ['channel_type' => 'store'],
            'finder_aliasname'    => 'storedinv',
        );

        $this->finder('console_mdl_dailyinventory', $params);
    }

    /**
     * storeItemIndex
     * @param mixed $dlyinv_id ID
     * @return mixed 返回值
     */
    public function storeItemIndex($dlyinv_id = 0)
    {
        $dlyinv = app::get('console')->model('dailyinventory')->dump($dlyinv_id);

        $store = [];
        if ($dlyinv['channel_id']) {
            $store = app::get('o2o')->model('store')->dump($dlyinv['channel_id']);
        }
        $actions = array(
            array('label' => '返回', 'href' => 'index.php?app=console&ctl=admin_dailyinventory&act=storeIndex&finder_id=' . $_GET['finder_id']),
        );

        $params = [
            'title'                  => sprintf('[%s]%s-日盘明细', $store['store_bn'], $store['name']),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'    => false,
            'use_buildin_recycle'    => false,
            'use_buildin_export'     => true,
            'use_buildin_import'     => false,
            'use_buildin_filter'     => true,
            'use_view_tab'           => false,
            'base_filter'            => ['dlyinv_id' => $dlyinv_id],
            'finder_aliasname'       => 'storedinv',
            'actions'                => $actions,

        ];

        $this->finder('console_mdl_dailyinventory_items', $params);
    }
}
