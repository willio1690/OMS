<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/10/30 15:17:01
 * @describe: 类
 * ============================
 */
class omeauto_ctl_autohold extends omeauto_controller {

    var $workground = "setting_tools";

    function index() {

        $params = array(
            'title' => '设置hold单规则',
            'actions' => array(
                array(
                    'label' => '新建',
                    'href' => 'index.php?app=omeauto&ctl=order_type&act=add&group_type=hold',
                    'target' => 'dialog::{width:700,height:480,title:\'新建分组规则\'}',
                ),
            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => true,
            'use_buildin_export' => false,
            'use_buildin_import' => false,
            'use_buildin_filter' => false,
            'use_view_tab' => false,
            'base_filter' => array('group_type'=>'hold'),
            'finder_aliasname' => 'omeauto_ctl_autohold',
        );
        $this->finder('omeauto_mdl_order_type', $params);
    }

    /**
     * 设置
     * @param mixed $tid ID
     * @return mixed 返回操作结果
     */

    public function set($tid)
    {
        $holdModel = app::get('omeauto')->model('autohold');
        $holdRow = $holdModel->db_dump($tid);
        if(!$holdRow['hold']) {
            $holdRow['hold'] = "all";
        }
        $this->pagedata['holdType'] = array('all'=>'整单hold单','part'=>'仅添加的销售物料hold单');
        $this->pagedata['tid'] = (int) $tid;
        $this->pagedata['data'] = $holdRow;
        $this->display('autohold/set.html');
    }

    /**
     * 保存
     * @return mixed 返回操作结果
     */
    public function save()
    {
        $url = "index.php?app=omeauto&ctl=autohold&act=index";
        $tid = $_POST['tid'];
        $hold = $_POST['hold'];
        $hours = $_POST['hours'];
        if($hold == "part") {
            $typeRow = app::get('omeauto')->model('order_type')->dump($tid);
            $skuRoleHad = false;
            foreach ($typeRow['config'] as $cv) {
                $role = json_decode($cv, 1);
                if($role['role'] == 'sku') {
                    $skuRoleHad = true;
                    break;
                }
            }
            if(!$skuRoleHad) {
                $this->splash("error", $url, "分组未设置活动订单规则，不能使用仅添加的销售物料hold单");
            }
        }
        $objHold = app::get('omeauto')->model('autohold');
        $objHold->delete(array('tid'=>$tid));
        $inData = array(
            'tid'=>$tid,
            'hold'=>$hold,
            'hours'=>$hours,
        );
        $objHold->insert($inData);
        $this->splash("success", $url, "设置完成");
    }
}