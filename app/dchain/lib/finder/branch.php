<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class dchain_finder_branch{
    public $addon_cols = "channel_id,config,node_id,node_type";
    
    public $column_edit = "操作";
    public $column_edit_width = "170";
    public $column_edit_order = "1";
    /**
     * column_edit
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_edit($row){
        $finder_id  = $_GET['_finder']['finder_id'];
        $channel_id = $row[$this->col_prefix.'channel_id'];
        // 编辑
        $btn = '<a href="index.php?app=dchain&ctl=admin_branch&act=edit&p[0]='.$channel_id.'&finder_id='.$finder_id.'" target="dialog::{width:650,height:400,title:\'外部优仓\'}">编辑</a>';

        return $btn;
    }
}
