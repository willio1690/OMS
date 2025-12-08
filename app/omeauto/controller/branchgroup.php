<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/10/22 16:12:45
 * @describe: 仓库分组
 * ============================
 */
class omeauto_ctl_branchgroup extends desktop_controller {
    var $workground = "setting_tools";

    function index() {
        $action=array(
            array(
                'label'  => $this->app->_('添加仓库分组'),
                'href'   => 'index.php?app=omeauto&ctl=branchgroup&act=addBranchGroup',
                'target'=>'dialog::{width:600,height:400,title:\'添加仓库分组\'}',
            ),
        );

        $params = array(
            'title' => '仓库分组',
            'use_buildin_recycle' => true,
            'actions' => $action
        );
        $this->finder('omeauto_mdl_branchgroup', $params);
    }

    /**
     * 添加BranchGroup
     * @return mixed 返回值
     */

    public function addBranchGroup() {
        $this->display('branchgroup/edit.html');
    }

    /**
     * edit
     * @return mixed 返回值
     */
    public function edit() {
        $bgId = (int) $_GET['bg_id'];
        $this->pagedata['data'] = app::get('omeauto')->model('branchgroup')->db_dump($bgId);
        $this->display('branchgroup/edit.html');
    }

    /**
     * doSave
     * @return mixed 返回值
     */
    public function doSave() {
        $bgId = $_POST['bg_id'];
        $name = $_POST['name'];
        $branchId = implode(',', $_POST['branch_id']);
        if (empty($name)) {
            $this->splash('error', '', '名称不能为空');
        }
        if (empty($branchId)) {
            $this->splash('error', '', '仓库不能为空');
        }
        $url = "index.php?app=omeauto&ctl=branchgroup&act=index";
        $data = array(
            'name' => $name,
            'branch_group' => $branchId,
            'last_modified' => time(),
        );
        if($bgId) {
            $data['bg_id'] = $bgId;
        } else {
            $data['createtime'] = time();
        }
        app::get('omeauto')->model('branchgroup')->db_save($data);
        $this->splash('success', $url, '保存完成');
    }
}