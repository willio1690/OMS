<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 客户分类列表
 *
 * @author wangbiao@shopex.cn
 * @version 2024.06.12
 */
class material_ctl_admin_customer_classify extends desktop_controller
{
    public $workground  = 'goods_manager';
    public $view_source = 'normal';
    
    var $title = '客户分类';
    private $_appName = 'material';
    
    private $_mdl = null; //model类
    private $_primary_id = null; //主键ID字段名
    private $_primary_bn = null; //单据编号字段名
    
    /**
     * __construct
     * @param mixed $app app
     * @return mixed 返回值
     */

    public function __construct($app)
    {
        parent::__construct($app);
        
        $this->_mdl = app::get($this->_appName)->model('customer_classify');
        
        //primary_id
        $this->_primary_id = 'class_id';
        
        //primary_bn
        $this->_primary_bn = 'class_bn';
    }
    
    /**
     * index
     * @return mixed 返回值
     */
    public function index()
    {
        $actions = array();
        
        //filter
        $base_filter = $this->getFilters();
        
        //button
        $buttonList = array();
        $buttonList['add'] = array(
            'label' => '添加',
            'href' => $this->url.'&act=add&finder_id='.$_GET['finder_id'],
            'target' => 'dialog::{width:600,height:300,title:\'新建客户分类\'}'
        );
        
        //view
        $_GET['view'] = (empty($_GET['view']) ? '0' : $_GET['view']);
        switch ($_GET['view'])
        {
            case '0':
                $actions[] = $buttonList['add'];
                break;
        }
        
        //导出权限
        $use_buildin_export = false;
        
        //params
        $orderby = $this->_primary_id . ' DESC';
        $params = array(
            'title' => $this->title,
            'base_filter' => $base_filter,
            'actions'=> $actions,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => true,
            'use_buildin_export' => $use_buildin_export,
            'use_buildin_import' => false,
            'use_buildin_filter' => true,
            'orderBy' => $orderby,
        );
        
        $this->finder('material_mdl_customer_classify', $params);
    }
    
    /**
     * _views
     * @return mixed 返回值
     */
    public function _views()
    {
        //filter
        $base_filter = $this->getFilters();
        
        //menu
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'), 'filter'=>$base_filter, 'optional'=>false),
        );
        
        foreach($sub_menu as $k => $v)
        {
            if (isset($v['filter'])){
                $v['filter'] = array_merge($base_filter, $v['filter']);
            }
            
            $sub_menu[$k]['filter'] = $v['filter'] ? $v['filter'] : null;
            $sub_menu[$k]['href'] = 'index.php?app='. $_GET['app'] .'&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view='.$k;
            
            //第一个TAB菜单没有数据时显示全部
            if($k == 0){
                //count
                $sub_menu[$k]['addon'] = $this->_mdl->count($v['filter']);
                if($sub_menu[$k]['addon'] == 0){
                    unset($sub_menu[$k]);
                }
            }else{
                //count
                $sub_menu[$k]['addon'] = $this->_mdl->viewcount($v['filter']);
            }
        }
        
        return $sub_menu;
    }
    
    /**
     * 公共filter条件
     * 
     * @return array
     */
    public function getFilters()
    {
        $base_filter = array();
        
        //check shop permission
//        $organization_permissions = kernel::single('desktop_user')->get_organization_permission();
//        if($organization_permissions){
//            $base_filter['org_id'] = $organization_permissions;
//        }
        
        return $base_filter;
    }
    
    public function add()
    {
        $this->pagedata['title'] = '添加客户分类';
        
        $this->display('admin/customer/add_classify.html');
    }
    
    public function edit($id)
    {
        $dataInfo = $this->_mdl->dump(array($this->_primary_id => $id), '*');
        
        $this->pagedata['dataInfo'] = $dataInfo;
        $this->display('admin/customer/add_classify.html');
    }
    
    /**
     * 保存
     * @return mixed 返回操作结果
     */
    public function save()
    {
        $this->begin($this->url.'&act=index');
        
        //post
        $class_id = intval($_POST['class_id']);
        $class_bn = $_POST['class_bn'];
        $class_name = addslashes($_POST['class_name']);
        $disabled = ($_POST['disabled'] == 'true' ? 'true' : 'false');
        
        //检查物料名称
        if(empty($class_bn)){
            $error_msg = '客户分类编码不能为空';
            $this->end(false, $error_msg);
        }
        
        if(empty($class_name)){
            $error_msg = '客户分类名称不能为空';
            $this->end(false, $error_msg);
        }
        
        //判断物料编码和物料条码只能是由数字英文下划线组成
        $reg_bn_code = "/^[0-9a-zA-Z\_\#\-\/]*$/";
        if(!preg_match($reg_bn_code, $class_bn)){
            $error_msg = '客户分类编码只支持(数字、英文、_下划线、-横线)';
            $this->end(false, $error_msg);
        }
        
        //info
        $dataInfo = [];
        if($class_id){
            $dataInfo = $this->_mdl->dump(array($this->_primary_id=>$class_id), '*');
        }
        
        //data
        $saveData = [
            'class_bn' => $class_bn,
            'class_name' => $class_name,
            'disabled' => $disabled,
        ];
        
        //add or update
        if($dataInfo){
            unset($saveData['class_bn']);
            
            //update
            $rs = $this->_mdl->update($saveData, array($this->_primary_id=>$class_id));
        }else{
            //insert
            $rs = $this->_mdl->insert($saveData);
        }
        
        //check
        if(is_bool($rs)) {
            $error_msg = ($dataInfo ? '更新' : '添加').'客户分类失败';
            $this->end(false, $error_msg);
        }
        
        $this->end(true, ($dataInfo ? '更新' : '添加').'客户分类成功');
    }
}