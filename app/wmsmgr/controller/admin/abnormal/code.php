<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * WMS仓储异常错误码
 *
 * @author wangbiao@shopex.cn
 * @version $Id: Z
 */
class wmsmgr_ctl_admin_abnormal_code extends desktop_controller
{
    var $workground = 'wms_manager';
    
    function index()
    {
        $base_filter = array();
        
        //action
        $actions = array();
        $actions[] = array(
            'label' => '添加异常错误码',
            'href' => 'index.php?app=wmsmgr&ctl=admin_abnormal_code&act=add',
            'target' => "dialog::{width:500,height:300,title:'添加异常错误码'}",
        );
        
        $actions[] = array(
                'label' => app::get('channel')->_('删除'),
                'confirm' => '确定删除选中项？',
                'submit' => 'index.php?app=wmsmgr&ctl=admin_abnormal_code&act=delete',
        );
        
        $actions[] = array(
                'label' => '导出模板',
                'href' => 'index.php?app=wmsmgr&ctl=admin_abnormal_code&act=exportTemplate',
                'target' => '_blank',
        );
        
        $actions[] = array(
                'label'=>'同步WMS异常错误码',
                'href' => 'index.php?app=wmsmgr&ctl=admin_abnormal_code&act=sync_errorcode&finder_id='. $_GET['finder_id'],
                'target' => "dialog::{width:350,height:200,title:'同步第三方仓储WMS异常错误码'}",
        );
        
        //params
        $params = array(
            'title' => 'WMS异常错误码列表',
            'actions' => $actions,
            'base_filter' => $base_filter,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => false,
            'use_buildin_export' => false,
            'use_buildin_import' => true,
        );
        $this->finder('wmsmgr_mdl_abnormal_code', $params);
    }
    
    function add(){
        $this->_edit();
    }
    
    function edit($abnormal_id){
        $this->_edit($abnormal_id);
    }
    
    private function _edit($abnormal_id=null)
    {
        if($abnormal_id){
            $abnormalObj = app::get('wmsmgr')->model('abnormal_code');
            $codeInfo = $abnormalObj->dump(array('abnormal_id'=>$abnormal_id), '*');
            $this->pagedata['data'] = $codeInfo;
        }
        
        //单据类型
        $schema = app::get('wmsmgr')->model('abnormal_code')->get_schema();
        $abnormal_types = $schema['columns']['abnormal_type']['type'];
        $this->pagedata['abnormal_types'] = $abnormal_types;
        
        $this->display('abnormal/add_code.html');
    }
    
    /**
     * 保存
     * @return mixed 返回操作结果
     */

    public function save()
    {
        $this->begin('index.php?app=wmsmgr&ctl=admin_abnormal_code&act=index');
        
        $abnormalObj = app::get('wmsmgr')->model('abnormal_code');
        
        $abnormal_id = intval($_POST['abnormal_id']);
        $abnormal_type = trim($_POST['abnormal_type']); //单据类型
        $abnormal_code = trim($_POST['abnormal_code']); //错误码
        $abnormal_name = trim($_POST['abnormal_name']); //错误描述
        
        if(empty($abnormal_type) || empty($abnormal_code) || empty($abnormal_name)){
            $error_msg = '无效的操作';
            $this->end(false, $error_msg);
        }
        
        //data
        $data = array(
                'abnormal_id' => $abnormal_id,
                'abnormal_type' => $abnormal_type,
                'abnormal_code' => $abnormal_code,
                'abnormal_name' => $abnormal_name,
                'create_time' => time(),
                'last_modified' => time(),
        );
        
        //check
        $abnormal_id = $data['abnormal_id'];
        if($abnormal_id){
            $codeInfo = $abnormalObj->dump(array('abnormal_id'=>$abnormal_id), '*');
            if($codeInfo){
                $data['abnormal_id'] = $codeInfo['abnormal_id'];
                
                unset($data['create_time']);
            }else{
                unset($data['abnormal_id']);
            }
        }
        
        //check
        if(strlen($data['abnormal_code']) < 3 || strlen($data['abnormal_code']) > 20){
            $error_msg = '错误码输入字符在3~20个字符';
            $this->end(false, $error_msg);
        }
        
        //code
        $filter = array('abnormal_code'=>$data['abnormal_code']);
        if($data['abnormal_id']){
            $filter['abnormal_id|noequal'] = $data['abnormal_id'];
        }
        
        $checkInfo = $abnormalObj->getList('abnormal_id', $filter);
        if($checkInfo){
            $error_msg = '错误码已经存在';
            return false;
        }
        
        $abnormalObj->save($data);
        
        $this->end(true, '添加成功');
    }

    /**
     * 删除
     * @return mixed 返回值
     */
    public function delete()
    {
        $this->begin('index.php?app=wmsmgr&ctl=admin_abnormal_code&act=index');
        
        $abnormalObj = app::get('wmsmgr')->model('abnormal_code');
        
        if($_POST['isSelectedAll'] == '_ALL_'){
            $this->end(false,'不支持全选');
        }
        
        if(empty($_POST['abnormal_id'])){
            $this->end(false, '没有可删除的记录');
        }
        
        foreach($_POST['abnormal_id'] as $abnormal_id)
        {
            //del
            $abnormalObj->delete(array('abnormal_id'=>$abnormal_id));
        }
        
        $this->end(true, '已经删除完成!');
    }
    
    /**
     * 异常错误码批量导入的模板
     * 
     * @param Null
     * @return String
     */
    public function exportTemplate()
    {
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=异常错误码导入模板-" . date('Ymd') . ".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        
        //导出标题
        $abnormalObj = app::get('wmsmgr')->model('abnormal_code');
        $title = $abnormalObj->exportTemplate();
        echo '"' . implode('","', $title) . '"';
        
        //模板案例
        $data[0] = array('发货单', '4100', '渠道未启用异常');
        $data[1] = array('发货单', '4300', '商品相关异常');
        $data[2] = array('售后单', '4200', '用户相关异常');
        
        foreach ($data as $items)
        {
            foreach ($items as $key => $val){
                $items[$key] = kernel::single('base_charset')->utf2local($val);
            }
            
            echo "\n";
            echo '"' . implode('","', $items) . '"';
        }
    }
    
    /**
     * 同步第三方仓储WMS异常错误码
     */
    public function sync_errorcode()
    {
        $abnormalObj = app::get('wmsmgr')->model('abnormal_code');
        
        //路由列表
        $storage = array();
        $sql = "SELECT channel_id,node_id,channel_bn,channel_name AS node_name,node_type FROM sdb_channel_channel WHERE channel_type='wms' AND node_id !=''";
        $tempList = $abnormalObj->db->select($sql);
        foreach ($tempList as $key => $val)
        {
            if($val['node_id'] == 'selfwms'){
                continue;
            }
            
            //现只支持yjdf京东一件代发仓储
            if($val['node_type'] != 'yjdf'){
                continue;
            }
            
            $storage[] = $val;
        }
        
        //check
        if(empty($storage)){
            die('现只支持yjdf京东一件代发仓储WMS异常错误码!');
        }
        
        $this->pagedata['storage'] = $storage;
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->display('abnormal/sync_errorcode.html');
    }
    
    /**
     * 同步第三方仓储WMS异常错误码
     */
    public function doSyncErrorcode()
    {
        $this->begin('index.php?app=wmsmgr&ctl=admin_abnormal_code&act=index');
        
        $node_id = $_POST['node_id'];
        if(empty($node_id)){
            $this->end(false, '没有获取到WMS仓储路由');
        }
        
        //[京东一件代发]查询订单是否可申请售后
        $channelObj = app::get('channel')->model('channel');
        $channelInfo = $channelObj->dump(array('node_id'=>$node_id),'channel_id,node_id,node_type,channel_bn');
        if($channelInfo['node_type'] != 'yjdf'){
            $this->end(false, '现只支持yjdf京东一件代发仓储同步售后原因');
        }
        $channel_id = $channelInfo['channel_id'];
        
        //request
        $result = kernel::single('erpapi_router_request')->set('wms', $channel_id)->reship_errorcode($channelInfo);
        if($result['rsp'] != 'succ')
        {
            $error_msg = $result['msg'];
            
            $this->end(false, $error_msg);
        }
        
        $this->end(true, app::get('base')->_('同步WMS异常错误码成功'), 3);
    }
}
