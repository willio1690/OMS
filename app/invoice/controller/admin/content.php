<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 发票内容管理
 */
class invoice_ctl_admin_content extends desktop_controller
{
    //发票内容管理   
    function index(){
        $this->finder('invoice_mdl_content', array(
            'title' => '发票内容管理',
            'actions'=>array(
                    array(
                            'label'=>'添加',
                            'href'=>'index.php?app=invoice&ctl=admin_content&act=add',
                            'target' => 'dialog::{width:400,height:200,title:\'新建发票内容\',resizeable:false}',
                    ),
            ),
            'use_buildin_set_tag' => false,
            'use_buildin_filter' => false,
            'use_buildin_new_dialog' => false,
            'use_buildin_tagedit' => false,
            'use_buildin_export' => false,
            'use_buildin_import' => false,
            'use_buildin_recycle'=> true,
        ));
    }
    
    //添加发票内容页面展示
    function add(){
        $this->pagedata["doAction"] = "doAdd";
        $this->page('admin/add_content.html');
    }
    
    //添加发票内容操作
    function doAdd()
    {
        $this->begin();
        $content_name = trim($_POST["content_name"]);
        if(!$content_name){
            $this->end(false, '发票内容不能为空');
        }
        
        $mdlInvoiceContent = $this->app->model('content');
        
        //检查是否有重复
        $rs_content = $mdlInvoiceContent->dump(array("content_name"=>$content_name));
        if($rs_content){
            $this->end(false, '此发票内容已经存在');
        }
        
        //新增
        $insert_arr = array('content_name'=>$content_name);
        $rs = $mdlInvoiceContent->insert($insert_arr);
        if($rs){
            $this->end(true, '添加成功');
        }else{
            $this->end(false, '添加失败');
        }
    }
    
    function edit(){
        $mdlInvoiceContent = $this->app->model('content');
        $this->pagedata["invoice_content"] = $mdlInvoiceContent->dump(array("content_id"=>$_GET["content_id"]));
        $this->pagedata["doAction"] = "doEdit";
        $this->page('admin/add_content.html');
    }

    function doEdit()
    {
        $this->begin();
        $content_id = $_POST["content_id"];
        
        //content_id为1：商品明细、不为数字、为0都不能进行编辑 
        if(!is_numeric($content_id) ||  !$content_id || intval($content_id) == 1){
            $this->end(false, '不能进行编辑');
        }
        
        $content_name = trim($_POST["content_name"]);
        if(!$content_name){
            $this->end(false, '发票内容不能为空');
        }
        
        $mdlInvoiceContent = $this->app->model('content');
        
        //检查是否有重复
        $rs_content = $mdlInvoiceContent->dump(array("content_name"=>$content_name));
        if($rs_content){
            $this->end(false, '此发票内容已经存在');
        }
        
        //更新
        $filter_arr = array("content_id"=>$content_id);
        $update_arr = array("content_name"=>$content_name);
        $result = $mdlInvoiceContent->update($update_arr,$filter_arr);
        if($result){
            $this->end(true, '编辑成功');
        }else{
            $this->end(false, '编辑失败');
        }
    }
}
