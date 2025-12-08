<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/11/30 15:32:48
 * @describe: 控制器
 * ============================
 */
class financebase_ctl_admin_shop_settlement_cainiaoitems extends desktop_controller {

    /**
     * index
     * @return mixed 返回值
     */

    public function index() {
        if(!isset($_POST['time_from'])) $_POST['time_from'] = date('Y-m-01', strtotime(date("Y-m-d")));
        if(!isset($_POST['time_to'])) $_POST['time_to'] = date('Y-m-d', strtotime("$_POST[time_from] +1 month -1 day"));
        $this->pagedata['shopdata'] = financebase_func::getShopList(financebase_func::getShopType());
        $this->pagedata['billCategory']= app::get('financebase')->model('expenses_rule')->getCainiaoBillCategory();
        kernel::single('financebase_cainiao')->set_params($_POST)->display();
    }

    /**
     * reConfirm
     * @return mixed 返回值
     */
    public function reConfirm()
    {
        if(!isset($_POST['time_from'])) $_POST['time_from'] = date('Y-m-01', strtotime(date("Y-m-d") . " -1 month"));
        if(!isset($_POST['time_to'])) $_POST['time_to'] = date('Y-m-d', strtotime("$_POST[time_from] +1 month -1 day"));
        $this->display('admin/cainiao/confirm_account.html');
    }

    /**
     * confirm
     * @return mixed 返回值
     */
    public function confirm()
    {
        $url = "index.php?app=financebase&ctl=admin_shop_settlement_cainiaoitems&act=index";

        if(!$_POST['time_from']) {
            $this->splash('error', $url, "账期开始时间需填写");
        }
        if(!$_POST['time_to']) {
            $this->splash('error', $url, "账期结束时间需填写");
        }

        $reConfirmTime = app::get('financebase')->getConf('bill.cainiao.reConfirmTime');
        !$reConfirmTime and $reConfirmTime = 0;
        $currentTime = time();
        if(600 > ($currentTime - $reConfirmTime) ){
            $this->splash('error', $url, "重新对账需间隔10分钟");
        }
        app::get('financebase')->setConf('bill.cainiao.reConfirmTime', $currentTime);
        $worker = "financebase_data_bill_businesstype_cainiao.doReconfirmTask";

        $mdlBill = app::get('financebase')->model('cainiao');

        $filter = array();
        $filter['time_from'] = $_POST['time_from'];
        $filter['time_to'] = $_POST['time_to'];
        $filter['confirm_status'] = $_POST['confirm_status'];

        $bill = $mdlBill->db_dump($filter, 'id');

        if($bill ){
            $oFunc = kernel::single('financebase_func');
            $oFunc->addTask('重新对账',$worker,$filter);
            $this->splash('success', $url, '上传队列成功，请等待处理！');
        }
        $this->splash('error', $url, '无数据');
    }
}