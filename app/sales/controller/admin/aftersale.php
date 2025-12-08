<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class sales_ctl_admin_aftersale extends desktop_controller
{

    public $name       = '单据';
    public $workground = 'invoice_center';

    /**
     * index
     * @return mixed 返回值
     */
    public function index()
    {
        $this->title = '售后单';

        $organization_permissions = kernel::single('desktop_user')->get_organization_permission();
        if ($organization_permissions) {
            $base_filter['org_id'] = $organization_permissions;
        }

        $params = array(
            'title'               => $this->title,
            'use_buildin_recycle' => false,
            'use_buildin_filter'  => true,
            'orderBy'             => 'aftersale_time desc',
            'base_filter'         => $base_filter,
        );

        if (isset($_GET['view']) && $_GET['view'] != 0) {
            $params['use_buildin_export'] = true;
        }
        #增加售后单导出权限
        $is_export                    = kernel::single('desktop_user')->has_permission('bill_export');
        $params['use_buildin_export'] = $is_export;

        $this->finder('sales_mdl_aftersale', $params);
    }

    /**
     * _views
     * @param mixed $base_filter base_filter
     * @return mixed 返回值
     */
    public function _views($base_filter)
    {
        $mdl_aftersale = $this->app->model('aftersale');

        $sub_menu = array(
            0 => array('label' => app::get('base')->_('全部'), 'filter' => array(), 'optional' => false),
            1 => array('label' => app::get('base')->_('退货单'), 'filter' => array('return_type' => 'return'), 'optional' => false),
            2 => array('label' => app::get('base')->_('换货单'), 'filter' => array('return_type' => 'change'), 'optional' => false),
            3 => array('label' => app::get('base')->_('退款单'), 'filter' => array('return_type' => 'refund'), 'optional' => false),
        );

        $i = 0;
        foreach ($sub_menu as $k => $v) {
            $v['filter'] = array_merge((array) $v['filter'], $base_filter);

            $sub_menu[$k]['filter'] = $v['filter'] ? $v['filter'] : null;
            $sub_menu[$k]['addon']  = $mdl_aftersale->count($v['filter']);
            $sub_menu[$k]['href']   = 'index.php?app=sales&ctl=' . $_GET['ctl'] . '&act=' . $_GET['act'] . '&view=' . $i++;
        }
        return $sub_menu;
    }
    
    /**
     * 加密字段显示明文
     *
     * @return void
     * @author
     **/
    public function showSensitiveData($aftersale_id, $fieldType='')
    {
        $aftersale = app::get('sales')->model('aftersale')->db_dump(array('aftersale_id'=>$aftersale_id), '*');
    
        #店铺信息
        $shop = app::get('ome')->model('shop')->getList('name,shop_type',array('shop_id'=>$aftersale['shop_id']),0,1);
        if ($aftersale['member_id']) {
            $member = app::get('ome')->model('members')->db_dump($aftersale['member_id'],'uname');
            $aftersale['uname'] = $member['uname'];
        }
        
        // 页面加密处理
        $aftersale['encrypt_body'] = kernel::single('ome_security_router',$shop['shop_type'])->get_encrypt_body($aftersale, 'aftersale', $fieldType);
        $this->splash('success',null,null,'redirect',$aftersale);
    }
}
