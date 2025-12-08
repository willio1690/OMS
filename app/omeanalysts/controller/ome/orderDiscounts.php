<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class   omeanalysts_ctl_ome_orderDiscounts extends desktop_controller
{
    
    /**
     * 订单优惠明细统计
     */
    public function index()
    {
        $organization_permissions = kernel::single('desktop_user')->get_organization_permission();
        if ($organization_permissions) {
            $_POST['org_id'] = $organization_permissions;
        }
        kernel::single('omeanalysts_ome_orderDiscounts')->set_params($_POST)->display();
    }
}