<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_ctl_admin_abnormal_cause extends desktop_controller{

    function index(){
        $this->finder('wms_mdl_abnormal_cause',array(
            'title'=>'异常原因列表',
            'actions' => array(
                array('label'=>'添加','href'=>'index.php?app=wms&ctl=admin_abnormal_cause&act=create','target'=>'dialog::{width:600,height:200,title:\'新建异常原因\'}'),
            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>true,
            'use_buildin_filter'=>true,
            'orderBy' =>'ac_id DESC'
        ));
    }

    function create(){
        $this->pagedata['title'] = '添加异常原因';
        $this->display('admin/abnormal/cause/detail.html');
    }
    
    function save(){
        $this->begin('index.php?app=wms&ctl=admin_abnormal_cause&act=index');
        $mdl_abnormal_cause = $this->app->model('abnormal_cause');
        $abnormal_cause = addslashes($_POST['abnormal_cause']);
        if($_POST['ac_id']==''){
            $rs = $mdl_abnormal_cause->dump(array('abnormal_cause'=>$abnormal_cause),'*');
            if(!empty($rs)){
                $this->end(false,app::get('base')->_('abnormal_cause已存在!不可以继续添加'));
            }
        }
        $this->end($mdl_abnormal_cause->save($_POST),app::get('base')->_('异常原因保存成功'));
    }
    
    function edit(){
        $ac_id = $_GET['ac_id'];
        $mdl_abnormal_cause = $this->app->model('abnormal_cause');
        $rs = $mdl_abnormal_cause->dump(array('ac_id'=>$ac_id), '*');
        $this->pagedata['abnormalCauseInfo'] = $rs;
        $this->singlepage('admin/abnormal/cause/detail.html');
    }
    
}
