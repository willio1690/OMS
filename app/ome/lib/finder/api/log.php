<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_finder_api_log
{
    public static $row = array();

    public $addon_cols = "log_id";
    public $column_edit = "操作";
    public $column_edit_width = "100";

    /**
     * column_edit
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_edit($row)
    {
        $log_id = $row['log_id'];
        $finder_id = $_GET['_finder']['finder_id'];
        
        $button = '<a href="index.php?app=ome&ctl=admin_api_log&act=show&finder_id=' . $finder_id . '&p[0]=' . $log_id . '" target="dialog::{width:1000,height:500,title:\'API日志详情\'}">详情</a>';
        
        return $button;
    }

    /* 详情
     *
     */

    public function detail_log($log_id)
    {
        
        $render = app::get('ome')->render();

        $apilog = app::get('ome')->model("api_log")->dump($log_id);
        
        // 使用封装的API日志数据处理方法
        $apilog = kernel::single('ome_api_func')->processApiLogData($apilog);

        // 生成唯一的DOM ID
        $dom_id = 'apilog_' . $log_id . '_' . time() . '_' . mt_rand(1000, 9999);
        $render->pagedata['apilog'] = $apilog;
        $render->pagedata['dom_id'] = $dom_id;

        return $render->fetch("admin/api/detail.html");
    }
}
