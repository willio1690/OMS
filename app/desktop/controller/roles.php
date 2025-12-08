<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class desktop_ctl_roles extends desktop_controller{
    
    var $workground = 'desktop_ctl_system';    
    var $obj_roles;
    
    /**
     * __construct
     * @param mixed $app app
     * @return mixed 返回值
     */
    public function __construct($app)
    {
        parent::__construct($app);
        $this->obj_roles = kernel::single('desktop_roles');
        header("cache-control: no-store, no-cache, must-revalidate");
    }
    
    function index(){
        $params = array(
            'title'=>app::get('desktop')->_('角色'),
            'actions'=>array(
                array('label'=>app::get('desktop')->_('新建角色'),'href'=>'index.php?ctl=roles&act=addnew','target'=>'dialog::{width:720,height:500,title:\''.app::get('desktop')->_('新建角色').'\'}'),
            )
        );
        $is_user_roles_add = kernel::single('desktop_user')->has_permission('user_roles_add');
        if(!$is_user_roles_add){
            unset($params['actions']);
        }
        $this->finder('desktop_mdl_roles',$params);
    }

    function addnew(){

        // 老的权限加载逻辑（注释掉，保留作为参考）
        /*
        $permissions = kernel::single('desktop_roles')->get_roles();

        $this->pagedata['widgets']     = $permissions['widgets'];
        $this->pagedata['workgrounds'] = $permissions['workgrounds'];
        $this->pagedata['adminpanels'] = $permissions['adminpanels'];
        $this->pagedata['others']      = $permissions['others'];
        */

        // 新的权限加载逻辑：使用与编辑角色相同的权限加载逻辑，保持一致性
        $menus = $this->app->model('menus');
        
        // 工作组权限
        $workgrounds = app::get('desktop')->model('menus')->getList('*',array('menu_type'=>'workground','disabled'=>'false','display'=>'true'));
        foreach($workgrounds as $k => $v){
            $workgrounds[$k]['permissions'] = $this->obj_roles->get_permission_per($v['menu_id'], array());
        }

        // 挂件权限
        $widgets = app::get('desktop')->model('menus')->getList('*',array('menu_type'=>'widgets'));
        foreach($widgets as $key=>$widget){
            $widgets[$key]['checked'] = false;
        }

        // 控制面板权限
        $adminpanels = $this->obj_roles->get_adminpanel(0, array());
        
        // 其他权限
        $others = $this->obj_roles->get_others(array());

        $this->pagedata['widgets']     = $widgets;
        $this->pagedata['workgrounds'] = $workgrounds;
        $this->pagedata['adminpanels'] = $adminpanels;
        $this->pagedata['others']      = $others;

        $this->page('users/add_roles.html');
    }
    
    function save()
    {
        $this->begin();
        $roles = $this->app->model('roles');
        if($roles->validate($_POST,$msg))
        {
            if($roles->save($_POST))
                $this->end(true,app::get('desktop')->_('保存成功'));
            else
                $this->end(false,app::get('desktop')->_('保存失败')); 
            
        }
        else
        {
            $this->end(false,$msg);
        }
    }
    
    
    function edit($roles_id){
        $param_id = $roles_id;
        $this->begin();
        if($_POST){
            if($_POST['role_name']==''){
                 $this->end(false,app::get('desktop')->_('工作组名称不能为空'));
            }

            if(!$_POST['workground']){
                $this->end(false,app::get('desktop')->_('请至少选择一个权限'));
            }

            $opctl = $this->app->model('roles');
            $result = $opctl->check_gname($_POST['role_name']);
            if($result && ($result!=$_POST['role_id'])) {
                $this->end(false,app::get('desktop')->_('该工作组名称已存在'));
            }

            if(!isset($_POST['data_authority'])){
                $_POST['data_authority'] = '';
            }

            if($opctl->save($_POST)){
                 $this->end(true,app::get('desktop')->_('保存成功'));
            }else{
               $this->end(false,app::get('desktop')->_('保存失败'));
            }
        }else{
            $opctl = $this->app->model('roles');
            $menus = $this->app->model('menus');
            $sdf_roles = $opctl->dump($param_id);
            $this->pagedata['roles'] = $sdf_roles;
            $workground = unserialize($sdf_roles['workground']);
            foreach((array)$workground as $v){
                $menuname = $menus->getList('*',array('menu_type' =>'menu','permission' => $v));
                foreach($menuname as $val){
                    $menu_workground[] = $val['workground'];
                }
            }
            $menu_workground = array_unique((array)$menu_workground);

            $workgrounds = app::get('desktop')->model('menus')->getList('*',array('menu_type'=>'workground','disabled'=>'false','display'=>'true'));
            foreach($workgrounds as $k => $v){
                $workgrounds[$k]['permissions'] = $this->obj_roles->get_permission_per($v['menu_id'],$workground);
                if(in_array($v['workground'],(array)$menu_workground)){
                    $workgrounds[$k]['checked'] = 1;
                }
            }

            $widgets = app::get('desktop')->model('menus')->getList('*',array('menu_type'=>'widgets'));
            foreach($widgets as $key=>$widget){
                if(in_array($widget['addon'],$workground)){
                    $widgets[$key]['checked'] = true;
                }
            }

            //数据权限
            $data_authority = unserialize($sdf_roles['data_authority']);
            if($data_authority){
                foreach($data_authority as $k => $v){
                    if($v){
                        $data_authorities[$v]['checked'] = 1;
                    }
                }
            }

            $this->pagedata['widgets'] = $widgets;
            $this->pagedata['workgrounds'] = $workgrounds;
            $this->pagedata['dataauthorities'] = $data_authorities;
            $this->pagedata['adminpanels'] = $this->obj_roles->get_adminpanel($param_id,$workground);
            $this->pagedata['others'] = $this->obj_roles->get_others($workground);
            $this->page('users/edit_roles.html');
        }
    }
}