<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 特性类目控制器
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

class flowctrl_ctl_admin_feature_group extends desktop_controller{

    var $workground = 'goods_manager';

    /**
     * 类目列表
     * 
     * @param Post
     * @return String
     */
    public function index(){
        
        $params = array(
            'title'=>'类目',
            'actions' => array(
                    array(
                        'label' => '新建',
                        'href' => 'index.php?app=flowctrl&ctl=admin_feature_group&act=add',
                        'target' => 'dialog::{width:600,height:400,title:\'新建类目\'}',
                    ),
            ),
            'base_filter' => $base_filter,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_filter'=>true,
            'use_buildin_export'=>false,
        );

         $this->finder('flowctrl_mdl_feature_group',$params);
    }

    /**
     * 物料特性添加
     * 
     * @param Null
     * @return String
     */
    public function add(){
        $featureObj = app::get('flowctrl')->model('feature');
        $featureList = $featureObj->getList('ft_id,ft_name', array(), 0, 1);
        $this->pagedata['features'] = $featureList;
        $this->page('admin/feature/group/add.html');
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
            'ftgp_name' => $params['name'],
            'config' => implode(",", $params['ft_id']),
        );
        app::get('flowctrl')->model('feature_group')->save($sdf);

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
        if(empty($params['name'])){
            $err_msg ="必填信息不能为空";
            return false;
        }

        $featureGrpObj = app::get('flowctrl')->model('feature_group');
        $featureGrpInfo = $featureGrpObj->getList('ftgp_name',array('ftgp_name'=>$params['name']));
        if($featureGrpInfo){
            $err_msg ="当前类目的名称已存在";
            return false;
        }

        if(!isset($params['ft_id'])){
            $err_msg ="类目没有关联特性，请检查";
            return false;
        }else{
            $featureObj = app::get('flowctrl')->model('feature');
            $featureList = $featureObj->getList('ft_name,type', array('ft_id'=>$params['ft_id']), 0, -1);
            $exist_type = array();
            foreach($featureList as $feature){
                if(in_array($feature['type'],array_keys($exist_type))){
                    $err_msg ="不能存在同一个事件节点的不同特性: ".$feature['ft_name']." 与 ".$exist_type[$feature['type']];
                    return false;
                }else{
                    $exist_type[$feature['type']] = $feature['ft_name'];
                }
            }
        }

        return true;
    }

    /**
     * 编辑类目内容
     * 
     * @param Int $ftgp_id
     * @return String
     */
    public function edit($ftgp_id){
        $flowctrlConfLib = kernel::single('flowctrl_conf');
        $nodeList = $flowctrlConfLib->getNodeList();
        $nodeList = array_merge(array(array('code'=>'-1','name'=>'--请选择特性--')),$nodeList);

        $featureGrpObj = app::get('flowctrl')->model('feature_group');
        $tmp_ftgp_id = intval($ftgp_id);
        $featureGrpInfo = $featureGrpObj->dump($tmp_ftgp_id);
        if(!$featureGrpInfo){
            $error_msg = '当前类目不存在，无法编辑';
        }

        //检查部分按钮是否只读不可能修改
        $readonly = $this->checkEditReadOnly($tmp_ftgp_id);

        $ft_ids = explode(",",$featureGrpInfo['config']);
        $count = count($ft_ids);
        $this->pagedata['ft_ids'] = $ft_ids;
        $this->pagedata['replacehtml'] = <<<EOF
<div id='hand-selected-product'>已选择了{$count}个特性,<a href='javascript:void(0);' onclick='feature_selected_show();'>查看选中的特性.</a></div>
EOF;

        $this->pagedata['feature_group_info'] = $featureGrpInfo;
        $this->pagedata['readonly'] = $readonly;
        $this->pagedata['nodes'] = $nodeList;
        $this->pagedata['error_msg'] = $error_msg;
        $this->page('admin/feature/group/edit.html');
    }

    /**
     * 检查类目某些内容是否可编辑
     * 
     * @param Int $ft_id
     * @return Array
     */
    public function checkEditReadOnly($ftgp_id){
        $readonly = array('type' => false);

        $basicMObj = app::get('material')->model('basic_material_ext');
        $basicMInfo = $basicMObj->getList('feature_group_id',array('feature_group_id'=>$ftgp_id));
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
            'ftgp_id' => $params['ftgp_id'],
            'ftgp_name' => $params['name'],
            'config' => implode(",", $params['ft_id']),
        );
        app::get('flowctrl')->model('feature_group')->save($sdf);

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
        if(empty($params['name']) || empty($params['ftgp_id'])){
            $err_msg ="必填信息不能为空";
            return false;
        }

        $featureGrpObj = app::get('flowctrl')->model('feature_group');
        $featureGrpInfo = $featureGrpObj->getList('ftgp_id',array('ftgp_name'=>$params['name']));
        if($featureGrpInfo && $featureGrpInfo[0]['ftgp_id'] != $params['ftgp_id']){
            $err_msg ="当前类目的名称已存在";
            return false;
        }

        $featureGrpInfo = $featureGrpObj->dump($params['ftgp_id']);
        if(!$featureGrpInfo){
            $err_msg ="当前类目不存在";
            return false;
        }

        if(!isset($params['ft_id'])){
            $err_msg ="类目没有关联特性，请检查";
            return false;
        }else{
            $featureObj = app::get('flowctrl')->model('feature');
            $featureList = $featureObj->getList('ft_name,type', array('ft_id'=>$params['ft_id']), 0, -1);
            $exist_type = array();
            foreach($featureList as $feature){
                if(in_array($feature['type'],array_keys($exist_type))){
                    $err_msg ="不能存在同一个事件节点的不同特性: ".$feature['ft_name']." 与 ".$exist_type[$feature['type']];
                    return false;
                }else{
                    $exist_type[$feature['type']] = $feature['ft_name'];
                }
            }
        }

        return true;
    }

    /**
     * @description 显示绑定的特性
     * @access public
     * @param void
     * @return void
     */
    public function showFeature()
    {
        $ft_id = kernel::single('base_component_request')->get_post('ft_id');
    
        if ($ft_id)
        {
            $this->pagedata['_input'] = array(
                    'name' => 'ft_id',
                    'idcol' => 'ft_id',
                    '_textcol' => 'ft_name',
            );
            
            $featureObj = app::get('flowctrl')->model('feature');
            $list = $featureObj->getList('ft_id,ft_name', array('ft_id'=>$ft_id),0,-1,'ft_id asc');
            $this->pagedata['_input']['items'] = $list;
        }
        
        $this->display('admin/feature/group/show_feature.html');
    }
}
