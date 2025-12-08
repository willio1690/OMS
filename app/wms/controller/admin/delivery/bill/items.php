<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 物流包裹明细列表
 * Class wms_ctl_admin_delivery_bill_items
 */
class wms_ctl_admin_delivery_bill_items extends desktop_controller
{
    public $name       = '物流包裹明细列表';
    public $workground = 'console_center';

    /**
     * 物流包裹列表
     */
    public function index()
    {
        $params = array(
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'    => false,
            'use_buildin_recycle'    => false,
            'use_buildin_import'     => false,
            'use_buildin_export'     => true,
            'use_buildin_filter'     => true,
            'use_view_tab'           => true,
            'actions'                => [],
            'title'                  => '物流包裹明细列表',
            'base_filter'            => [],
        );

        $this->finder('wms_mdl_delivery_bill_items', $params);
    }
}
