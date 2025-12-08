<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class sales_ctl_admin_sales extends desktop_controller
{

    public $name       = '单据';
    public $workground = 'invoice_center';

    /**
     * index
     * @return mixed 返回值
     */
    public function index()
    {
        #增加销售单导出权限
        $is_export   = kernel::single('desktop_user')->has_permission('bill_export');
        $this->title = '销售单';

        $organization_permissions = kernel::single('desktop_user')->get_organization_permission();
        if ($organization_permissions) {
            $base_filter['org_id'] = $organization_permissions;
        }

        // 发货单号 店铺编号  仓库编号
        $params = array(
            'title'               => $this->title,
            'use_buildin_recycle' => false,
            'use_buildin_export'  => $is_export,
            'use_buildin_filter'  => true,
            // 'orderBy'             => 'sale_time desc',
            'orderBy'             => 'sale_id desc',
            'base_filter'         => $base_filter,
            'object_method'       => [
                'count'   => 'finder_count',
                'getlist' => 'finder_getList',
            ]
        );
        $this->finder('sales_mdl_sales', $params);
    }

    /**
     * _views
     * @param mixed $base_filter base_filter
     * @return mixed 返回值
     */
    public function _views($base_filter)
    {
        $salesObj = app::get('sales')->model('sales');

        $sub_menu = array(
            0 => array('label' => app::get('base')->_('全部'), 'filter' => array(), 'optional' => false),
            1 => array('label' => app::get('base')->_('金额异常'), 'filter' => array('check' => 'true'), 'optional' => false),
            
        );

        $i = 0;
        foreach ($sub_menu as $k => $v) {
            $v['filter'] = array_merge((array) $v['filter'], $base_filter);

            $sub_menu[$k]['filter'] = $v['filter'] ? $v['filter'] : null;
            $sub_menu[$k]['addon']  = 'showtab';//$salesObj->count($v['filter']);
            $sub_menu[$k]['href']   = 'index.php?app=sales&ctl=' . $_GET['ctl'] . '&act=' . $_GET['act'] . '&view=' . $i++;
        }
        return $sub_menu;
    }

}
