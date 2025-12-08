<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_ctl_admin_refund extends desktop_controller
{
    public $name       = "退款单";
    public $workground = "invoice_center";

    /**
     * index
     * @return mixed 返回值
     */
    public function index()
    {
        #增加单据导出权限
        $is_export = kernel::single('desktop_user')->has_permission('bill_export');

        $organization_permissions = kernel::single('desktop_user')->get_organization_permission();
        if ($organization_permissions) {
            $base_filter['org_id'] = $organization_permissions;
        }

        $this->finder('ome_mdl_refunds', array(
            'title'                  => '退款单',
            'actions'                => array(),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'    => false,
            'use_buildin_recycle'    => false,
            'use_buildin_export'     => $is_export,
            'use_buildin_import'     => false,
            'use_buildin_filter'     => true,
            'base_filter'            => $base_filter,
        ));
    }
}
