<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_ctl_admin_server extends desktop_controller
{

    public $name       = "服务端管理";
    public $workground = "channel_center";

    /**
     * index
     * @return mixed 返回值
     */
    public function index()
    {
        $title = '服务端管理';
        $this->finder('o2o_mdl_server', array(
            'title'                  => $title,
            'actions'                => array(
                array('label' => '添加服务端', 'href' => 'index.php?app=o2o&ctl=admin_server&act=add&finder_id=' . $_GET['finder_id'], 'target' => 'dialog::{width:650,height:450,title:\'添加服务端\'}'),
            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'    => false,
            'use_buildin_recycle'    => true,
            'use_buildin_export'     => false,
            'use_buildin_import'     => false,
            'use_buildin_filter'     => true,
        ));
    }

    /*
     * 添加服务端
     */

    public function add()
    {
        $this->_edit();
    }

    /*
     * 编辑服务端
     */

    public function edit($server_id)
    {
        $this->_edit($server_id);
    }

    /**
     * _edit
     * @param mixed $server_id ID
     * @return mixed 返回值
     */
    public function _edit($server_id = null)
    {
        $serverObj = $this->app->model("server");

        $type_list                   = o2o_conf_server::getTypeList();
        $this->pagedata['type_list'] = $type_list;

        if ($server_id) {
            $server_id                = intval($server_id);
            $server                   = $serverObj->dump($server_id);
            $this->pagedata['server'] = $server;
        }

        $this->pagedata['title'] = '添加/编辑服务端';
        $this->display("admin/system/server.html");
    }

    /**
     * 保存
     * @return mixed 返回操作结果
     */
    public function save()
    {
        $serverObj = $this->app->model("server");

        $url = 'index.php?app=o2o&ctl=admin_server&act=index';
        $this->begin($url);
        $save_data = $_POST['server'];

        if ($_POST['config']) {
            $save_data['config'] = serialize($_POST['config']);
        }

        // if ($save_data['type'] == 'openapi') {
        //     $save_data['node_id'] = $save_data['node_type'];
        // }

        //新增并且服务端选择阿里全渠道的
        if (!$save_data["server_id"] && $save_data["type"] == "taobao") {
            $rs_taobao_server = $serverObj->dump(array("type" => "taobao"));
            if (!empty($rs_taobao_server)) {
                $this->end(false, '阿里全渠道服务端已存在，不能重复添加。');
            }
        }

        if (!$save_data['old_server_bn']) {
            $shop_detail = $serverObj->dump(array('server_bn' => $save_data['server_bn']), 'server_bn');
            if ($shop_detail['server_bn']) {
                $this->end(false, '编码已存在，请重新输入。');
            }
        }

        $rt  = $serverObj->save($save_data);
        $msg = $rt ? '保存成功' : '保存失败';

        $this->end($rt, $msg);
    }

    /**
     * confightml
     * @param mixed $server_id ID
     * @param mixed $type type
     * @return mixed 返回值
     */
    public function confightml($server_id, $type)
    {
        $server                   = $this->app->model("server")->dump($server_id);
        $server['config']         = (array) unserialize($server['config']);
        $this->pagedata['server'] = $server;
        switch ($type) {
            case 'taobao':
                //目前已去除选择 阿里全渠道 下面 加载选择主店铺的选择项 统一在全渠道配置页完成
                break;
            default:
                break;
        }

        $this->display('admin/system/server/' . $type . '.html');
    }
}
