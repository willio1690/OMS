<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_ctl_admin_payment_cfg extends desktop_controller
{
    public $name       = "支付方式";
    public $workground = "setting_tools";
    /**
     * index
     * @return mixed 返回值
     */
    public function index()
    {
        $this->finder('ome_mdl_payment_cfg', array(
            'title'                 => '支付方式管理',
            'actions'               => array(
                // array('label' => '新增', 'href' => $this->url.'&act=add&finder_id='.$_GET['finder_id'],'target'=>'dialog::{width:690,height:200,title:\'新增支付方式\'}"'),
                array('label' => '同步', 'href' => 'index.php?app=ome&ctl=admin_payment_cfg&act=getPayment&finder_id=' . $_GET['finder_id']),
            ),
            'use_buildin_recycle'   => false,
            'use_buildin_selectrow' => false,
            'use_buildin_filter'    => true,
        ));
    }

    /**
     * 获取Payment
     * @return mixed 返回结果
     */
    public function getPayment()
    {
        $this->begin('index.php?app=ome&ctl=admin_payment_cfg');
        $shopObj  = app::get('ome')->model('shop');
        $shopList = $shopObj->getList('shop_id');
        foreach ($shopList as $shop) {
            kernel::single("ome_payment_func")->sync_payments($shop['shop_id']);
        }
        $this->end(true, app::get('base')->_('发送成功'));
    }

    /**
     * 添加
     * @return mixed 返回值
     */
    public function add()
    {
        $this->display('admin/payment/add.html');
    }

    /**
     * do_add
     * @return mixed 返回值
     */
    public function do_add()
    {
    }
}
