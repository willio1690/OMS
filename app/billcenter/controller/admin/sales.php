<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 唯品会JIT销售单
 */
class billcenter_ctl_admin_sales extends desktop_controller
{
    /**
     * index
     *
     **/
    public function index()
    {
        $params = [
            'title' => '销售单',
            'use_buildin_recycle' => false,
            'use_buildin_filter'=>true,
        ];
        $this->finder('billcenter_mdl_sales', $params);
    }
}