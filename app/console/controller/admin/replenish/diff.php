<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 补货差异单任务
 *
 * @author wangbiao<wangbiao@shopex.cn>
 * @version $Id: Z
 */
class console_ctl_admin_replenish_diff extends desktop_controller
{
    /**
     * index
     * @return mixed 返回值
     */

    public function index()
    {
        $base_filter = array();
        
        //action
        $actions = array();
        
        //params
        $params = array(
                'use_buildin_new_dialog' => false,
                'use_buildin_set_tag' => false,
                'use_buildin_recycle' => false,
                'use_buildin_import' => false,
                'use_buildin_export' => false,
                'use_buildin_filter' => true,
                'use_view_tab' => true,
                'actions' => $actions,
                'title' => '补货差异单',
                'base_filter' => $base_filter,
                'orderBy' => 'diff_id DESC',
        );
        
        $this->finder('console_mdl_replenish_diff', $params);
    }
}
