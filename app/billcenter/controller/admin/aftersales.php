<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 唯品会JIT售后单
 */
class billcenter_ctl_admin_aftersales extends desktop_controller
{
    /**
     * index
     *
     **/
    public function index()
    {
        $params = [
            'title' => '售后单',
            'use_buildin_recycle' => false,
            'use_buildin_filter'=>true,
            'orderBy'                => 'id desc',
        ];
        $this->finder('billcenter_mdl_aftersales', $params);
    }
}