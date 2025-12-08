<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_ctl_admin_branchset extends desktop_controller{
    var $name = "仓库设置";
    var $workground = "setting_tools";

    function index(){
       $branchtype = app::get('wms')->getConf('wms.branchset.type');
       $this->pagedata['branchtype'] =  $branchtype;
       //售后质检商品入库类型
       $goodsBranchType = app::get('ome')->getConf('ome.aftersale.goods.branch.type');
       $this->pagedata['goods_branch_type'] =  $goodsBranchType;
       $setData                                = array();
       $setData['return_auto_branch']          = app::get('ome')->getConf('return.auto_branch');
       $setData['return_auto_shop_branch']     = app::get('ome')->getConf('return.auto_shop_branch');
       $this->pagedata['setData'] = $setData;
       $shopMdl = app::get('ome')->model('shop');
       $shopList = $shopMdl->getlist('shop_id,name',array('s_type'=>'1','s_status'=>'2'));

       $this->pagedata['shopList'] = $shopList;
       $branchMdl = app::get('ome')->model('branch');
       $branchList = $branchMdl->getlist('branch_id,name',array('check_permission'=>'false','type'=>array('main','aftersale','damaged')));

       $this->pagedata['branchList'] = $branchList;
       $this->page('admin/branch/branchset.html');
    }

    
    /**
     * 保存设置
     * @param 
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function save()
    {
        $this->begin();
        $settype = $_POST['set']['branchtype'];
        $goodsBranchType = $_POST['set']['goods_branch_type'];
        $_POST['set']['return.auto_shop_branch'] = [];
        if($_POST['auto_shop_branch']) {
            foreach ($_POST['auto_shop_branch']['shop_id'] as $key => $shop_id) {
                $_POST['set']['return.auto_shop_branch'][$shop_id] = $_POST['auto_shop_branch']['branch_id'][$key];
            }
        }
        app::get('wms')->setConf('wms.branchset.type',$settype);
        app::get('ome')->setConf('ome.aftersale.goods.branch.type',$goodsBranchType);
        app::get('ome')->setConf('return.auto_branch', $_POST['set']['return.auto_branch']);
        app::get('ome')->setConf('return.auto_shop_branch', $_POST['set']['return.auto_shop_branch']);
        $this->end(true,'保存成功');
    }
     
}
?>
