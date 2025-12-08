<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wmsmgr_finder_channel
{
    var $addon_cols = "channel_id";
    var $column_edit = "操作";
    var $column_edit_width = "180";
    var $column_edit_order = "1";
    
    function column_edit($row)
    {
        $materialChannelMdl = app::get('material')->model('basic_material_channel');
        
        $channel_id = $row[$this->col_prefix . 'channel_id'];
        $bind_btn = '';
        $finder_id = $_GET['_finder']['finder_id'];
        $url = "index.php?app=wmsmgr&ctl=admin_channel";
        
        $bind_btn .= sprintf('<a href="index.php?app=wmsmgr&ctl=admin_wms&act=yjdf_sync_material&p[0]=%s" target="dialog::{width:600,height:350,title:\'初始化物料\'}" style="">初始化</a>', $channel_id);
        
        //渠道商品总数
        $countNum = $materialChannelMdl->count(array('channel_id'=>$channel_id));
        if(empty($countNum)){
            $delMsg = '确定要删除【'. $row['channel_name'] .'】此渠道吗？';
            $bind_btn .= sprintf(' | <a href="javascript:if(confirm(\'%s\')){W.page(\'%s&act=delChannel&p[0]=%s&finder_id=%s\', $extend({method: \'get\'}, JSON.decode({})), this);}void(0);">删除</a>', $delMsg, $url, $channel_id, $finder_id);
        }
        
        return $bind_btn;
    }
}
