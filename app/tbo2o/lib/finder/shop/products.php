<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class tbo2o_finder_shop_products
{
    public $addon_cols    = 'id,is_sync,sync_time,visibled';
    
    //操作
    var $column_edit  = '操作';
    var $column_edit_width = 80;
    var $column_edit_order = 5;
    function column_edit($row)
    {
        $finder_id       = $_GET['_finder']['finder_id'];
        $id              = $row[$this->col_prefix.'id'];
        $is_sync         = intval($row[$this->col_prefix.'is_sync']);
        
        if($is_sync == 1) 
        {
            $button    = '<a href="index.php?app=tbo2o&ctl=admin_shop_products&act=edit&p[0]='.$id.'&finder_id='.$finder_id.'" target="_blank">编辑</a>';
        }
        elseif ($is_sync == 2)
        {
            $button    = '<a href="index.php?app=tbo2o&ctl=admin_shop_products&act=edit&p[0]='.$id.'&finder_id='.$finder_id.'" target="_blank">编辑</a>';
            
            $href     = "index.php?app=tbo2o&ctl=admin_shop_products&act=updateSyncTaobao&p[0]={$id}&finder_id={$finder_id}";
            $button    .= "&nbsp;|&nbsp;<a href='javascript:void(0);' target='download' onclick='if(confirm(\"你确定更新该后端商品信息至淘宝吗？\")){href=\"{$href}\";}'>更新</a>";
        }
        else 
        {
            $button    = '<a href="index.php?app=tbo2o&ctl=admin_shop_products&act=edit&p[0]='.$id.'&finder_id='.$finder_id.'" target="_blank">编辑</a>';
            
            $href     = "index.php?app=tbo2o&ctl=admin_shop_products&act=syncTaobaoSingle&p[0]={$id}&finder_id={$finder_id}";
            $button    .= "&nbsp;|&nbsp;<a href='javascript:void(0);' target='download' onclick='if(confirm(\"你确定同步该后端商品信息至淘宝吗？\")){href=\"{$href}\";}'>同步</a>";
        }
        
        return $button;
    }

    var $column_is_sync = '同步状态';
    var $column_is_sync_width = 80;
    var $column_is_sync_order = 60;
    function column_is_sync($row)
    {
        $is_sync    = intval($row[$this->col_prefix.'is_sync']);
        if($is_sync == 1)
        {
            return '已同步';
        }
        elseif($is_sync == 2)
        {
            return '同步失败';
        }
        else
        {
            return '';
        }
    }

    var $column_sync_time = '同步时间';
    var $column_sync_time_width = 130;
    var $column_sync_time_order = 65;
    function column_sync_time($row)
    {
        $is_sync    = intval($row[$this->col_prefix.'is_sync']);
        $sync_time  = $row[$this->col_prefix.'sync_time'];
        if($is_sync == 1)
        {
            return date('Y-m-d H:i', $sync_time);
        }
        else 
        {
            return '';
        }
    }
    
    var $column_visibled = '销售状态';
    var $column_visibled_width = 70;
    var $column_visibled_order = 80;
    function column_visibled($row)
    {
        $visibled    = $row[$this->col_prefix.'visibled'];
        if($visibled == 1)
        {
            return '在售';
        }
        else
        {
            return '关闭';
        }
    }
}