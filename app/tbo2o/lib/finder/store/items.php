<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class tbo2o_finder_store_items
{
    public $addon_cols    = 'id,is_bind,bind_time';
    
    //操作
    var $column_edit  = '操作';
    var $column_edit_width = 80;
    var $column_edit_order = 10;
    function column_edit($row)
    {
        $finder_id       = $_GET['_finder']['finder_id'];
        $id              = $row[$this->col_prefix.'id'];
        $is_bind         = intval($row[$this->col_prefix.'is_bind']);
        
        if($is_bind == 1) 
        {
            $button    = '<a href="index.php?app=tbo2o&ctl=admin_store_items&act=unbind&p[0]='.$id.'&finder_id='.$finder_id.'">解绑</a>';
        }
        else 
        {
            $button    = '<a href="index.php?app=tbo2o&ctl=admin_store_items&act=bind&p[0]='.$id.'&finder_id='.$finder_id.'">绑定</a>';
        }
        
        return $button;
    }

    var $column_is_bind = '绑定状态';
    var $column_is_bind_width = 80;
    var $column_is_bind_order = 90;
    function column_is_bind($row)
    {
        $is_bind    = intval($row[$this->col_prefix.'is_bind']);
        if($is_bind == 1)
        {
            return '已绑定';
        }
        else
        {
            return '未绑定';
        }
    }

    var $column_bind_time = '绑定时间';
    var $column_bind_time_width = 120;
    var $column_bind_time_order = 95;
    function column_bind_time($row)
    {
        $bind_time    = $row[$this->col_prefix.'bind_time'];
        if($bind_time)
        {
            return date('Y-m-d H:i', $bind_time);
        }
        else 
        {
            return '';
        }
    }
}