<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeauto_ctl_order_split extends omeauto_controller
{
    var $workground = "setting_tools";
    var $base_filter = array('group_type' => 'order');

    /**
     * index
     * @return mixed 返回值
     */
    public function index()
    {
        $params = array(
            'title' => '拆单规则设置',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => true,
            'use_buildin_export' => false,
            'use_buildin_import' => false,
            'use_buildin_filter' => false,
            'use_view_tab' => false,
        );

        $params['actions'] = array(
            array('label' => '新建', 'icon' => '', 'href' => 'index.php?app=omeauto&ctl=order_split&act=add', 'target' => 'dialog::{width:760,height:480,title:\'新建拆单规则\'}'),
        );

        $this->finder('omeauto_mdl_order_split', $params);
    }

    /**
     * 添加
     * @return mixed 返回值
     */
    public function add()
    {
        $this->pagedata['split_type'] = $this->app->model('order_split')->getSplitType();
        $this->page('order/type/split.html');
    }

    /**
     * edit
     * @param mixed $sid ID
     * @return mixed 返回值
     */
    public function edit($sid)
    {
        $this->pagedata['info'] = $this->app->model('order_split')->dump($sid);
        $this->pagedata['split_type'] = $this->app->model('order_split')->getSplitType();
        $this->page('order/type/split.html');
    }

    /**
     * 获取SplitSpecial
     * @return mixed 返回结果
     */
    public function getSplitSpecial()
    {
        $sid = (int)$_GET['sid'];
        $splitType = $_POST['split_type'];
        if ($sid) {
            $row = $this->app->model('order_split')->db_dump($sid);
            if ($row['split_type'] == $splitType || $_POST['from'] == 'split') {
                $this->pagedata['data'] = $row;
            }
        }
        $this->pagedata['special_data'] = kernel::single('omeauto_split_router', $splitType)->getSpecial();
        $html = '';
        try {
            $file = 'order/type/split/' . $splitType . '.html';
            $html = $this->fetch($file);
        } catch (Exception $e) {
        }
        echo $html;
    }

    /**
     * 保存
     * @return mixed 返回操作结果
     */
    public function save()
    {
        $sdf = $_POST;
        
        //check
        if(empty($sdf['name'])){
            echo '请填写拆单规则名称';
            exit;
        }
        
        if(empty($sdf['split_type'])){
            echo '请选择拆单类型';
            exit;
        }
        
        $sid = intval($_REQUEST['sid']);
        if (!empty($sid) && $sid > 0) {
            $sdf['sid'] = $sid;
        } else {
            $sdf['createtime'] = time();
        }
        
        list($rs, $msg) = kernel::single('omeauto_split_router', $sdf['split_type'])->preSaveSdf($sdf);
        if(!$rs) {
            echo '保存失败!', $msg;exit;
        }
        
        app::get('omeauto')->model('order_split')->save($sdf);

        echo "SUCC";

    }
}