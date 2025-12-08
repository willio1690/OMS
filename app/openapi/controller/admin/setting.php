<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_ctl_admin_setting extends desktop_controller{
    var $name = "基础设置";
    var $workground = "setting_tools";

    /**
     * index
     * @return mixed 返回值
     */
    public function index(){
        $this->finder('openapi_mdl_setting',array(
            'title'=>'基础配置',
            'actions' => array(
                 array('label'=>'添加','href'=>'index.php?app=openapi&ctl=admin_setting&act=addNew','target'=>'_blank'),
            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>true,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'orderBy' =>'s_id DESC'
	    ));
    }

    /**
     * 添加New
     * @return mixed 返回值
     */
    public function addNew(){
        $conf = array();

        foreach (openapi_conf::getMethods() as $key => $value) {
            $group = $value['group'] ? $value['group'] : 'openapiuse';

            $conf[$group][$key] = $value;
        }

        $this->pagedata['configLists'] = $conf;

        $this->singlepage('admin/setting/detail.html');
    }

    /**
     * edit
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function edit($id){
        $conf = array();

        foreach (openapi_conf::getMethods() as $key => $value) {
            $group = $value['group'] ? $value['group'] : 'openapiuse';

            $conf[$group][$key] = $value;
        }

        $this->pagedata['configLists'] = $conf;

        $settingObj = $this->app->model('setting');
        $settingInfo = $settingObj->dump($id);
        $this->pagedata['settingInfo'] = $settingInfo;
        $this->singlepage('admin/setting/detail.html');
    }

    /**
     * 保存
     * @return mixed 返回操作结果
     */
    public function save(){
        $this->begin('index.php?app=openapi&ctl=admin_setting&act=index');
        $settingObj = $this->app->model('setting');

        $data = array(
            'name' => trim($_POST['name']),
            'config' => $_POST['config'],
            'status' => $_POST['status'] == 1 ? 1 : 0,
            'interfacekey' => trim($_POST['interfacekey']),
            'is_data_mask' => $_POST['is_data_mask'] == 1 ? 1 : 0,
        );

        if(empty($_POST['s_id'])){
            $settingInfo = $settingObj->dump(array('code'=>$_POST['code']),'s_id');
            if($settingInfo){
                $this->end(false,'标识已存在');
            }
            $data['code'] = trim($_POST['code']);
        }else{
            $settingInfo = $settingObj->dump(array('s_id'=>$_POST['s_id']),'s_id,code');
            if(!$settingInfo){
                $this->end(false,'配置信息不存在');
            }
    		$data['s_id'] = $_POST['s_id'];
        }

        if($settingObj->save($data)){
            $this->end(true,'保存成功');
        }else{
            $this->end(false,'保存失败');
        }

    }

    /**
     * 设置Status
     * @param mixed $sid ID
     * @param mixed $status status
     * @return mixed 返回操作结果
     */
    public function setStatus($sid,$status){
        $settingObj = $this->app->model('setting');
        $data = array(
            's_id' => $sid,
            'status' => $status,
        );
        $settingObj->save($data);

        echo sprintf("<script>parent.MessageBox.success('更新成功!');parent.finderGroup['%s'].refresh();</script>",$_GET["finder_id"]);

        exit;
    }
}
