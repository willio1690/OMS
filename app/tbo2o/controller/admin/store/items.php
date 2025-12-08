<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class tbo2o_ctl_admin_store_items extends desktop_controller {

    var $name = '淘宝门店关联宝贝';
    var $workground = 'tbo2o_center';

    function index()
    {
        $base_filter = array();
        $params = array(
                'title'=>'淘宝门店关联宝贝',
                'actions' => array(
                        array(
                                'label' => '批量绑定',
                                'submit' => 'index.php?app=tbo2o&ctl=admin_store_items&act=bindingTbStoreItems&finder_id='.$_GET['finder_id'],
                                'target' => 'dialog::{title:\'批量绑定\'}',
                        ),
                ),
                'base_filter' => $base_filter,
                'use_buildin_set_tag'=>false,
                'use_buildin_recycle'=>false,
                'use_buildin_filter'=>true,
                'use_buildin_export'=>false,
                'use_buildin_import'=>false,
        );
        
        $this->finder('tbo2o_mdl_store_items', $params);
    }
    
    /**
     * 淘宝门店关联宝贝单个绑定
     */
    function bind($id){
        $return_result = kernel::single('tbo2o_common_tbo2oapi')->tbStoreItemStoreBanding($id,"ADD",$errormsg);
        if ($return_result){
            $url = 'javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();';
            $this->splash('success',$url,"绑定成功");
        }else{
            $this->splash('error', null, "绑定失败：".$errormsg);
        }
    }
    
    /**
     * 淘宝门店关联宝贝单个解绑
     */
    function unbind($id){
        $return_result = kernel::single('tbo2o_common_tbo2oapi')->tbStoreItemStoreBanding($id,"DELETE",$errormsg);
        if ($return_result){
            $url = 'javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();';
            $this->splash('success',$url,"解绑成功");
        }else{
            $this->splash('error', null, "解绑失败：".$errormsg);
        }
    }
    
    //批量绑定
    /**
     * bindingTbStoreItems
     * @return mixed 返回值
     */
    public function bindingTbStoreItems(){
        //获取选取数据
        $this->_request = kernel::single('base_component_request');
        $data = $this->_request->get_post();
        $mdlTbo2oStoreItems = app::get('tbo2o')->model('store_items');
        if ($data["isSelectedAll"] == "_ALL_"){
            //选择全部 拿出is_bind=0未绑定的所有数据
            $data_list = $mdlTbo2oStoreItems->getList("id",array("is_bind"=>"0"));
        }else{
            //取选中的项 拿出sync是1未同步或者2同步失败的store_id
            $ids = $data['id'];
            $data_list = $mdlTbo2oStoreItems->getList("id",array("is_bind"=>"0","id|in"=>$ids));
        }
        $ids = array();
        foreach ($data_list as $val_d_l){
            $ids[] = $val_d_l["id"];
        }
        //每次最多执行50条记录
        if(count($ids) > 50){
            echo '批量操作每次最多可以执行50条记录!';
            exit;
        }
        //加载批量模板
        $loadList[] = array('name'=>'淘宝门店关联宝贝批量绑定','flag'=>'all');
        //同步页面
        $url = 'index.php?app=tbo2o&ctl=admin_store_items&act=execBindingTbStoreItems';
        if ($_GET['redirectUrl']){
            $this->pagedata['redirectUrl'] = 'index.php?'.http_build_query($_GET['redirectUrl']);
        }
        $this->pagedata['url'] = $url;
        $this->pagedata['loadList'] = $loadList;
        
        $_POST = array();
        $_POST['time'] = time();
        $_POST['ids'] = json_encode($ids);
        if($_POST){
            $inputhtml = '';
            foreach ($_POST as $key => $val){
                $params = array(
                        'type' => 'hidden',
                        'name' => $key,
                        'value' => $val,
                );
                $inputhtml .= utils::buildTag($params,'input');
            }
            $this->pagedata['inputhtml'] = $inputhtml;
        }
        
        $this->display('admin/store/binding_tb_store_items.html');
    }
    
    function execBindingTbStoreItems(){
        //页码
        $page    = intval($_GET['page']);
        $page    = ($page > 0 ? $page : 1);
        $flag    = $_GET['flag'];
        
        $id_list = ($_POST['ids'] ? json_decode($_POST['ids'], true) : '');
        $totalResults  = count($id_list);
        if(empty($id_list)){
            $this->splash('error', null, '没有可执行的数据');
        }
        
        //已完成同步
        if($page > $totalResults){
            $msg        = '同步完成';
            $msgData    = array('errormsg'=>'', 'totalResults'=>$totalResults, 'downloadRate'=>100, 'downloadStatus'=>'finish');
            $this->splash('success', null, $msg,'redirect', $msgData);
        }
        
        //正在同步
        $id = $id_list[$page - 1];
        $return_result = kernel::single('tbo2o_common_tbo2oapi')->tbStoreItemStoreBanding($id,"ADD",$errormsg);
        if($return_result === false){
            $this->splash('error', null, $errormsg);
        }else{
            $msg = '正在同步中...';
            $downloadRate = ($page / $totalResults) * 100;
            $msgData = array('errormsg'=>$errormsg, 'totalResults'=>$totalResults, 'downloadRate'=>intval($downloadRate), 'downloadStatus'=>'running');
            $this->splash('success', null, $msg,'redirect', $msgData);
        }
    }
    
}