<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 物料特性控制器
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

class flowctrl_ctl_admin_feature extends desktop_controller{

    var $workground = 'goods_manager';

    /**
     * 物料特性列表
     * 
     * @param Post
     * @return String
     */
    public function index(){
        
        $params = array(
            'title'=>'特性',
            'actions' => array(
                    array(
                        'label' => '新建',
                        'href' => 'index.php?app=flowctrl&ctl=admin_feature&act=add',
                        'target' => 'dialog::{width:600,height:600,title:\'新建特性\'}',
                    ),
            ),
            'base_filter' => $base_filter,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_filter'=>true,
            'use_buildin_export'=>false,
        );

         $this->finder('flowctrl_mdl_feature',$params);
    }

    /**
     * 物料特性添加
     * 
     * @param Null
     * @return String
     */
    public function add(){
        $flowctrlConfLib = kernel::single('flowctrl_conf');
        $NodeList = $flowctrlConfLib->getNodeList();
        $NodeList = array_merge(array(array('code'=>'-1','name'=>'--请选择特性--')),$NodeList);
        $this->pagedata['nodes'] = $NodeList;
        $this->page('admin/feature/add.html');
    }

    /**
     * ajax异步加载特性配置页
     * 
     * @param Post
     * @return String
     */
    public function getNodesCnfByFeature(){
        if(!$_POST['node']){return;}
        $node = $_POST['node'];
        $ft_id = $_POST['ft_id'];

        $cnf = array();
        $flowctrlConfLib = kernel::single('flowctrl_conf');
        $tmp_cnf[$node] = $flowctrlConfLib->getNodeCnfByNode($node);
        $cnf = array_merge($cnf,$tmp_cnf);


        $featureObj = app::get('flowctrl')->model('feature');
        $tmp_ft_id = intval($ft_id);
        $featureInfo = $featureObj->dump($tmp_ft_id);

        $this->pagedata['cnf'] = $featureInfo['config'];
        $this->pagedata['conf_list'] = $cnf;
        $this->display('admin/feature/conf_item.html');
    }

    /**
     * 保存新增特性
     * 
     * @param Post
     * @return String
     */
    public function toAdd(){
        $params = $_POST;

        //检查参数
        if(!$this->checkAddParams($params, $err_msg)){
            echo $err_msg;exit;
        }
        $sdf = array(
            'ft_name' => $params['name'],
            'type' => $params['node'],
            'config' => $params['cnf'],
        );
        app::get('flowctrl')->model('feature')->save($sdf);

        echo "SUCC";exit;
    }

    /**
     * 特性新增参数检查
     * 
     * @param Array $params 
     * @param String $err_msg
     * @return Boolean
     */
    public function checkAddParams(&$params, &$err_msg){
        if(empty($params['name']) || empty($params['node'])){
            $err_msg ="必填信息不能为空";
            return false;
        }

        $featureObj = app::get('flowctrl')->model('feature');
        $featureInfo = $featureObj->getList('ft_name',array('ft_name'=>$params['name']));
        if($featureInfo){
            $err_msg ="当前特性的名称已存在";
            return false;
        }

        if(!isset($params['cnf'])){
            $err_msg ="特性没有相关配置信息，请检查";
            return false;
        }

        return true;
    }

    /**
     * 编辑特性内容
     * 
     * @param Int $ft_id
     * @return String
     */
    public function edit($ft_id){
        $flowctrlConfLib = kernel::single('flowctrl_conf');
        $nodeList = $flowctrlConfLib->getNodeList();
        $nodeList = array_merge(array(array('code'=>'-1','name'=>'--请选择特性--')),$nodeList);

        $featureObj = app::get('flowctrl')->model('feature');
        $tmp_ft_id = intval($ft_id);
        $featureInfo = $featureObj->dump($tmp_ft_id);
        if(!$featureInfo){
            $error_msg = '当前特性不存在，无法编辑';
        }

        //检查部分按钮是否只读不可能修改
        $readonly = $this->checkEditReadOnly($ft_id);

        $this->pagedata['feature_info'] = $featureInfo;
        $this->pagedata['readonly'] = $readonly;
        $this->pagedata['nodes'] = $nodeList;
        $this->pagedata['error_msg'] = $error_msg;
        $this->page('admin/feature/edit.html');
    }

    /**
     * 检查特性某些内容是否可编辑
     * 
     * @param Int $ft_id
     * @return Array
     */
    public function checkEditReadOnly($ft_id){
        $readonly = array('type' => false);

        $basicMObj = app::get('material')->model('basic_material_ext');
        $basicMInfo = $basicMObj->getList('feature_id',array('feature_id'=>$ft_id));
        if($basicMInfo){
            $is_type_readonly = true;
        }

        if($is_type_readonly){
            $readonly['type'] = true;
        }

        return $readonly;
    }

    /**
     * 保存编辑过后的特性
     * 
     * @param Post
     * @return String
     */
    public function toEdit(){
        $params = $_POST;

        //检查参数
        if(!$this->checkEditParams($params, $err_msg)){
            echo $err_msg;exit;
        }
        $sdf = array(
            'ft_id' => $params['ft_id'],
            'ft_name' => $params['name'],
            'type' => $params['node'],
            'config' => $params['cnf'],
        );
        app::get('flowctrl')->model('feature')->save($sdf);

        echo "SUCC";exit;
    }

    /**
     * 特性新增参数检查
     * 
     * @param Array $params 
     * @param String $err_msg
     * @return Boolean
     */
    public function checkEditParams(&$params, &$err_msg){
        if(empty($params['name']) || empty($params['node']) || empty($params['ft_id'])){
            $err_msg ="必填信息不能为空";
            return false;
        }

        $featureObj = app::get('flowctrl')->model('feature');
        $featureInfo = $featureObj->getList('ft_id',array('ft_name'=>$params['name']));
        if($featureInfo && $featureInfo[0]['ft_id'] != $params['ft_id']){
            $err_msg ="当前特性的名称已存在";
            return false;
        }

        $featureInfo = $featureObj->dump($params['ft_id']);
        if(!$featureInfo){
            $err_msg ="当前特性不存在";
            return false;
        }

        if(!isset($params['cnf'])){
            $err_msg ="特性没有相关配置信息，请检查";
            return false;
        }

        return true;
    }
}
