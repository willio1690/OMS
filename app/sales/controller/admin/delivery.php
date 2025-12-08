<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class sales_ctl_admin_delivery extends desktop_controller
{
    
    public $name = '单据';
    public $workground = 'invoice_center';
    
    /**
     * index
     * @return mixed 返回值
     */
    public function index()
    {
        $this->title = '发货销售单';
        
        $organization_permissions = kernel::single('desktop_user')->get_organization_permission();
        if ($organization_permissions) {
            $base_filter['org_id'] = $organization_permissions;
        }
        
        $params = array(
            'title'               => $this->title,
            'use_buildin_recycle' => false,
            'use_buildin_export'  => true,
            'use_buildin_filter'  => true,
            'orderBy'             => 'delivery_id desc',
            'base_filter'         => $base_filter,
        );
        $this->finder('sales_mdl_delivery_order', $params);
    }
    
    
    /**
     * item
     * @return mixed 返回值
     */
    public function item()
    {
        $this->title = '发货销售明细单';
        
        $organization_permissions = kernel::single('desktop_user')->get_organization_permission();
        if ($organization_permissions) {
            $base_filter['org_id'] = $organization_permissions;
        }
        
        $params = array(
            'title'               => $this->title,
            'use_buildin_recycle' => false,
            'use_buildin_export'  => true,
            'use_buildin_filter'  => true,
            'orderBy'             => 'id desc',
            'base_filter'         => $base_filter,
        );
        $this->finder('sales_mdl_delivery_order_item', $params);
    }
    
    
}
