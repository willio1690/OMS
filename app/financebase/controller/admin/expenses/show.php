<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/11/26 9:54:17
 * @describe: 控制器
 * ============================
 */
class financebase_ctl_admin_expenses_show extends desktop_controller {

    /**
     * index
     * @return mixed 返回值
     */

    public function index() {
        if(!isset($_POST['time_from'])) $_POST['time_from'] = date('Y-m-01', strtotime(date("Y-m-d")));
        if(!isset($_POST['time_to'])) $_POST['time_to'] = date('Y-m-d', strtotime("$_POST[time_from] +1 month -1 day"));
        $actions = array();
        $actions[] = array(
            'label' => '导出',
            'class' => 'export',
            'href' => 'index.php?app=financebase&ctl=admin_expenses_show&act=index&action=export',
        );
        $params = array(
                'title'=>'费用对账拆分总览',
                'use_buildin_set_tag'=>false,
                'use_buildin_setcol'=>false,
                'use_buildin_refresh'=>false,
                'use_buildin_selectrow'=>false,
                'use_buildin_filter'=>false,
                'use_buildin_export'=>false,
                'use_buildin_import'=>false,
                'use_buildin_recycle'=>false,
                'use_save_filter'=>false,
                'top_extra_view'=>array('financebase'=>'admin/expenses/show.html'),
                'actions'=>$actions,
        );
        $this->pagedata = $_POST;
        if($_GET['action'] != 'export') {
            $this->pagedata['detail'] = app::get('financebase')->model('expenses_show')->getIndexTotal($_POST);
        }
        $shopdata = financebase_func::getShopList(financebase_func::getShopType());
        $this->pagedata['shopdata']= $shopdata;
        $this->pagedata['billCategory']= app::get('financebase')->model('expenses_rule')->getBillCategory();
        $this->finder('financebase_mdl_expenses_show', $params);
    }
}