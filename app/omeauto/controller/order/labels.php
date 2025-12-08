<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 订单标签管理
 *
 * @author wangbiao@shopex.cn
 * @version $Id: Z
 */
class omeauto_ctl_order_labels extends omeauto_controller
{
    var $workground = 'setting_tools';
    
    function index()
    {
        $base_filter = array();
        $actions = array();
        
        $actions[] = array(
            'label' => '新建',
            'href' => 'index.php?app=omeauto&ctl=order_labels&act=add',
            'target' => 'dialog::{width:500,height:300,title:\'新建订单标签\'}',
        );
        
        $actions[] = array(
                'label' => '删除',
                'confirm' => '你确定要删除此条记录吗？',
                'submit'=>'index.php?app=omeauto&ctl=order_labels&act=del_label',
                'target'=>'refresh'
        );
        
        $params = array(
                'title' => '订单标签管理',
                'actions' => $actions,
                'use_buildin_new_dialog' => false,
                'use_buildin_set_tag' => false,
                'use_buildin_recycle' => false,
                'use_buildin_export' => false,
                'use_buildin_import' => false,
                'use_buildin_filter' => false,
                'use_view_tab' => false,
                'base_filter' => $base_filter,
        );
        $this->finder('omeauto_mdl_order_labels', $params);
    }
    
    function add()
    {
        $this->pagedata['data'] = array();
        
        $this->page('order/label/label_add.html');
    }
    
    function edit($label_id)
    {
        $labelObj = app::get('omeauto')->model('order_labels');
        
        //标记信息
        $labelInfo = $labelObj->dump(array('label_id'=>$label_id), '*');
        
        $this->pagedata['data'] = $labelInfo;
        
        $this->page('order/label/label_add.html');
    }
    
    /**
     * 保存
     * @return mixed 返回操作结果
     */

    public function save()
    {
        $labelObj = app::get('omeauto')->model('order_labels');
        
        $labelLib = kernel::single('omeauto_order_label');
        
        $url = "index.php?app=omeauto&ctl=order_labels&act=index";
        
        //标记信息
        $data = array(
            'label_id' => intval($_POST['label_id']),
            'label_code' => trim($_POST['label_code']),
            'label_name' => trim($_POST['label_name']),
            'label_color' => trim($_POST['label_color']),
            'source' => kernel::single('desktop_user')->get_login_name(),
            'create_time' => time(),
            'last_modified' => time(),
        );
        
        //check
        $error_msg = '';
        $isCheck = $labelLib->check_label_params($data, $error_msg);
        if(!$isCheck){
            $this->splash('error', $url, $error_msg);
        }
        
        //save
        $return = $labelObj->save($data);
        if(!$return){
            $this->splash("error", $url, '保存失败');
        }
        
        $this->splash("success", $url, '标记保存成功');
    }
    
    /**
     * 删除标签
     */
    public function del_label()
    {
        $this->begin('index.php?app=omeauto&ctl=order_labels&act=index');
        
        $labelObj = app::get('omeauto')->model('order_labels');
        $orderLabelObj = app::get('ome')->model('bill_label');
        
        if($_POST['isSelectedAll'] == '_ALL_'){
            $this->end(false,'不支持全选');
        }
        
        if(empty($_POST['label_id'])){
            $this->end(false, '没有可删除的记录');
        }
        
        foreach($_POST['label_id'] as $label_id)
        {
            //判断是否已经有订单使用
            $labelInfo = $orderLabelObj->dump(array('label_id'=>$label_id), 'bill_type,bill_id,label_name');
            if($labelInfo){
                $this->end(false, $labelInfo['label_name'] .'已经被订单使用,不能删除');
            }
            
            //del
            $labelObj->db->exec("DELETE FROM sdb_omeauto_order_labels WHERE label_id=".$label_id);
        }
        
        $this->end(true, '已经删除完成!');
    }
}