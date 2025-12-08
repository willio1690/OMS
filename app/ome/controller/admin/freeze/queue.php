<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 仓/商品 冻结对列表
 *
 * @access public
 * @author maxiaochen<maxiaochen@shopex.cn>
 * @version 1.0 queue.php 2025-02-14
 */
class ome_ctl_admin_freeze_queue extends desktop_controller
{

    var $workground = "adminpanel";

    /**
     * _views
     * @return mixed 返回值
     */

    public function _views()
    {
        $branchFQMdl   = app::get('ome')->model('branch_freeze_queue');
        $materialFQMdl = app::get('ome')->model('material_freeze_queue');

        $base_filter = array();
        $sub_menu    = array(
            0 => array('label' => __('仓冻结队列'), 'filter' => $base_filter, 'mdl' => $branchFQMdl),
            1 => array('label' => __('商品冻结队列'), 'filter' => $base_filter, 'mdl' => $materialFQMdl),
        );

        foreach ($sub_menu as $k => $v) {
            $sub_menu[$k]['filter'] = $v['filter'] ? $v['filter'] : null;
            $sub_menu[$k]['addon']  = $v['mdl']->viewcount($v['filter']);
            $sub_menu[$k]['href']   = 'index.php?app=ome&ctl=admin_freeze_queue&act=index&view=' . $k;
        }

        return $sub_menu;
    }

    /**
     * index
     * @return mixed 返回值
     */
    public function index()
    {
        !$_GET['view'] && $_GET['view'] = 0;

        switch ($_GET['view']) {
            case '0':
                $this->title = '仓冻结队列列表';
                $mdl         = "ome_mdl_branch_freeze_queue";
                break;
            case '1':
                $this->title = '商品冻结队列列表';
                $mdl         = "ome_mdl_material_freeze_queue";
                break;
            default:
                $this->title = '仓冻结队列列表';
                $mdl         = "ome_mdl_branch_freeze_queue";
                break;
        }

        $actions = array();
        $params  = array(
            'actions'             => $actions,
            'title'               => $this->title,
            'use_buildin_set_tag' => false,
            'use_buildin_filter'  => false,
            'use_buildin_export'  => false,
            'use_buildin_import'  => false,
            'use_buildin_recycle' => false,
        );

        $this->finder($mdl, $params);
    }

}
