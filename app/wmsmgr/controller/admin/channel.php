<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wmsmgr_ctl_admin_channel extends desktop_controller
{
    //渠道管理列表
    /**
     * index
     * @return mixed 返回值
     */
    public function index()
    {
        $actions = array();
        
        //是否允许添加新渠道
        $isAddChannel = kernel::single('wmsmgr_channel')->checkAddChannel();
        
        //添加新渠道
        if($isAddChannel){
            $actions['add'] = array(
                    'label' => '新增渠道',
                    'href' => 'index.php?app=wmsmgr&ctl=admin_channel&act=add',
                    'target' => "dialog::{width:600,height:300,title:'渠道添加  '}",
            );
        }
        
        //params
        $params = array(
            'title'                  => '渠道管理列表',
            'actions'                => $actions,
            'use_buildin_recycle'    => false,
            'use_buildin_selectrow'  => false,
            'orderBy' => 'id ASC',
        );
        $this->finder('wmsmgr_mdl_channel', $params);
    }
    
    function add() {
        $this->_edit();
    }
    
    function edit($id) {
        $this->_edit($id);
    }
    
    private function _edit($id = NULL) {
        if ($id) {
            $channelObj = $this->app->model('channel');
            $channel_detail = $channelObj->dump($id);
            $this->pagedata['channel'] = $channel_detail;
        }
        $this->pagedata['wms'] = app::get('wmsmgr')->model('wms')->getList('*',array('node_type'=>'yjdf','node_id|noequal'=>''));

        $this->display("add_channel.html");
    }
    
    function saveChannel()
    {
        $channelMdl = app::get('wmsmgr')->model('channel');
        
        $url = 'index.php?app=wmsmgr&ctl=admin_channel&act=index';
        $this->begin($url);
    
        $ad_info = kernel::single('ome_func')->getDesktopUser();
        $data = array('createtime'=>time(),'op_id'=>$ad_info['op_id'],'op_name'=>$ad_info['op_name']);
        if ($_POST['channel']['channel_id'])
            $data['channel_id'] = trim($_POST['channel']['channel_id']);
        if ($_POST['channel']['channel_name'])
            $data['channel_name'] = $_POST['channel']['channel_name'];
        if ($_POST['channel']['wms_id']){
            $data['wms_id'] = $_POST['channel']['wms_id'];
        }
        
        //是否允许添加新渠道
        $isAddChannel = kernel::single('wmsmgr_channel')->checkAddChannel();
        if(!$isAddChannel){
            $this->end(false, '请先购买绑定多渠道的授权服务!');
        }
        
        if ($channelMdl->dump(array('channel_id' => $data['channel_id']))) {
            $this->end(false, app::get('base')->_('渠道ID重复，保存失败'));
        } else {
            $rt = $channelMdl->save($data);
            
            //已绑定的渠道总数
            $countNum = $channelMdl->count();
            
            //更新授权服务已使用次数(Monitor每次固定+1授权次数)
            $authNum = 1;
            $result = kernel::single('wmsmgr_channel')->updateServiceAuthNums('kepler_channel', $authNum);
            
            $this->end($rt, app::get('base')->_($rt ? '保存成功' : '保存失败'));
        }
    }

    /**
     * 删除渠道
     * 
     * @param unknown $channel_id
     * @return string
     */
    public function delChannel($channel_id)
    {
        $channelMdl = app::get('wmsmgr')->model('channel');
        $materialChannelMdl = app::get('material')->model('basic_material_channel');
        
        $url = 'javascript:parent.finderGroup["'.$_GET['finder_id'].'"].refresh();';
        
        if(empty($channel_id)){
            $this->splash('error', $url, '操作出错：无效的操作。');
        }
        
        //渠道商品总数
        $countNum = $materialChannelMdl->count(array('channel_id'=>$channel_id));
        if($countNum){
            $this->splash('error', $url, '此渠道已经有关联商品,禁止删除。');
        }
        
        //删除
        $result = $channelMdl->delete(array('channel_id'=>$channel_id));
        if(!$result){
            $this->splash('success', $url, '删除渠道失败');
        }
        
        $this->splash('success', $url, '删除渠道成功');
    }
    
    /**
     * test
     * @return mixed 返回值
     */
    public function test()
    {
        $msg = '';
        kernel::single('material_autotask_timer_batchchannelmaterial')->process([],$msg);
        echo $msg;
    }
}