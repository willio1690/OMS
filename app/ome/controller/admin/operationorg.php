<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_ctl_admin_operationorg extends desktop_controller{

    var $workground = 'desktop_ctl_system';

    function index(){
        $this->finder('ome_mdl_operation_organization',array(
            'title'=>'运营组织',
            'actions' => array(
                array('label'=>'添加','href'=>'index.php?app=ome&ctl=admin_operationorg&act=add','target'=>'dialog::{width:600,height:400,title:\'添加运营组织\'}'),
            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'orderBy' =>'org_id ASC'
        ));
    }

    function add(){
        $this->pagedata['title'] = '添加运营组织';
        $this->display('admin/operationorg/detail.html');
    }

    function save(){
        // 如果是通过 dialog 打开的（no_redirect=1），不需要设置跳转 URL（dialog 会自动关闭）
        $url = '';
        if (empty($_GET['no_redirect']) && empty($_POST['no_redirect'])) {
            $url = 'index.php?app=ome&ctl=admin_operationorg&act=index';
        }
        $this->begin($url);
        $operationOrgObj = $this->app->model('operation_organization');
        $code = addslashes($_POST['code']);

        if($_POST['org_id']==''){
            $orgInfo = $operationOrgObj->dump(array('code'=>$code),'*');
            if(!empty($orgInfo)){
                $this->end(false,app::get('base')->_('编码已存在!不可以重复添加'));
            }
        }else{
            $orgInfo = $operationOrgObj->dump(array('code'=>$code),'*');
            if($orgInfo['org_id'] != $_POST['org_id']){
                $this->end(false,app::get('base')->_('编码已被占用!不可以重复添加'));
            }
        }
        kernel::single('desktop_roles')->syncPermissionQueue();
        $this->end($operationOrgObj->save($_POST),app::get('base')->_('运营组织保存成功'));

    }

    function edit($org_id){
        $org_id = $org_id ? $org_id : $_GET['org_id'];
        $operationOrgObj = $this->app->model('operation_organization');
        $orgs = $operationOrgObj->dump(array('org_id' => $org_id), '*');

        $this->pagedata['orgInfo'] = $orgs;
        $this->pagedata['title'] = '编辑运营组织';
        $this->display('admin/operationorg/detail.html');
    }

    /**
     * 获取运营组织列表（用于刷新下拉框）
     */
    function getOrgs() {
        $operationOrgObj = $this->app->model('operation_organization');
        $orgs = $operationOrgObj->getList('org_id,name', array(), 0, -1);
        echo json_encode($orgs);
        exit;
    }

}

