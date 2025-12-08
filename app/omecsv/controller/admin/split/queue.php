<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 队列控制层
 * Class ome_ctl_admin_split_queue
 */
class omecsv_ctl_admin_split_queue extends desktop_controller
{
    // 任务列表
    /**
     * index
     * @return mixed 返回值
     */

    public function index()
    {
        $base_filter = [];
        $actions = [
            [
                'label'  => '导入任务',
                'href'   => sprintf('%s&act=task_import', $this->url),
                'target' => 'dialog::{width:760,height:300,title:\'导入任务\'}',
            ],
        ];
        $params      = array(
            'title'               => '任务列表',
            'actions'             => $actions,
            'base_filter'         => $base_filter,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => false,
            'use_buildin_filter'  => true,
            'use_bulidin_view'    => true,
            'use_buildin_export'  => false,
            'use_buildin_import'  => false,
            'orderBy'             => 'queue_id desc',
        );
        
        $this->finder('omecsv_mdl_queue', $params);
    }
    
    /**
     * task_import
     * @return mixed 返回值
     */
    public function task_import()
    {
        $this->pagedata['beginurl'] = $this->url . '&act=index';
        $this->display('admin/task/import/normal.html');
    }
}