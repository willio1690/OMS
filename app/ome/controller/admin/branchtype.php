<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_ctl_admin_branchtype extends desktop_controller
{
    public $name       = "仓库类型列表";
    public $workground = "goods_manager";

    /**
     * index
     * @return mixed 返回值
     */
    public function index()
    {
        $filter = array();
        $actions = array(
            array(
                'label' => '添加', 
                'href' => 'index.php?app=ome&ctl=admin_branchtype&act=add&singlepage=false&finder_id=' . $_GET['finder_id'], 
                'target'=>'dialog::{width:690,height:400,title:\'添加仓库类型\'}"',
            ),

            // array(
            //     'label'=>'删除', 
            //     'submit' => 'index.php?app=ome&ctl=admin_branchtype&act=delete',
            //      'confirm' => '确认要删除吗？'
            //  ),
        );

        $params = array(
            'title'                  => '仓库类型列表',
            'actions'                => $actions,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'    => false,
            'use_buildin_recycle'    => false,
            'use_buildin_export'     => false,
            'use_buildin_import'     => false,
            'base_filter'            => $filter,
          
        );
        $this->finder('ome_mdl_branch_type', $params);
    }


    /**
     * 添加
     * @return mixed 返回值
     */
    public function add(){

        $this->display("admin/system/branchtype.html");
    }

    /**
     * 保存
     * @return mixed 返回操作结果
     */
    public function save(){

        $this->begin();

        $typeMdl = app::get('ome')->model('branch_type');
        $data = array(

            'type_code' =>  trim($_POST['type_code']),
            'type_name' =>  trim($_POST['type_name']),
            'type_desc' =>  trim($_POST['type_desc']),

            'source'    =>  'local',
        );

       
        $types = $typeMdl->db_dump(array('type_code'=>trim($_POST['type_code'])),'type_id');
        if($_POST['type_id']){
            $data['type_id'] = $_POST['type_id'];
            unset($data['type_code']);
            if($types && $types['type_id']!=$_POST['type_id']){
                $this->end(false,$_POST['type_code'].':编码已存在');
            }
        }else{
            

            if($types){
                $this->end(false,$_POST['type_code'].':编码已存在');
            }

        }
        $rs = $typeMdl->db_save($data);
        if($rs){
            $this->end(true,'保存成功');
        }else{
            $this->end(false,'保存失败');
        }
        
    }

    /**
     * edit
     * @param mixed $type_id ID
     * @return mixed 返回值
     */
    public function edit($type_id){

        $typeMdl = app::get('ome')->model('branch_type');
        $types = $typeMdl->db_dump(array('type_id'=>$type_id),'*');

        $this->pagedata['types'] = $types;
        $this->display("admin/system/branchtype.html");
    }

    /**
     * del
     * @param mixed $type_id ID
     * @return mixed 返回值
     */
    public function del($type_id)
    {
        $this->begin('index.php?app=ome&ctl=admin_branchtype&act=index');
        
        
        $typeMdl = app::get('ome')->model('branch_type');
        $types = $typeMdl->dump($type_id);

        if ($types['source'] == 'system'){
            $this->end(false, '系统内置类型不允许删除');
        }

        
        $branchMdl = app::get('ome')->model('branch');
        if ($branchMdl->count(['type' => $types['type_code'], 'check_permission' => 'false'])) {
            $this->end(false, '该类型下有仓库，不允许删除');
        }
       
        if ($typeMdl->delete(array('type_id'=>$type_id))) {
            $this->end(true, '删除成功');
        }

        $this->end(true, '删除失败');
    }
    
}
