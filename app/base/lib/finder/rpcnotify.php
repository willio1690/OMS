<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class base_finder_rpcnotify
{
    var $detail_basic = '基本信息';
    var $addon_cols   = "msg";
    
    function detail_basic($id)
    {
        $render    = app::get('base')->render();
        $noticeMdl = app::get('base')->model('rpcnotify');
        $shopMdl   = app::get('ome')->model('shop');
        $info      = $noticeMdl->dump(array('id' => $id), 'msg');
        if ($info) {
            $msg = json_decode($info['msg'], true);
            if (is_array($msg)) {
                $shop = $shopMdl->dump(array('node_id' => $msg['node_id']), 'name');
            }
        }
        
        $data['msg']       = $msg['info'];
        $data['shop_name'] = $shop['name'];
        //修改已有缓存
        $cacheInfo = cachecore::fetch('system_notice_data');
        if ($cacheInfo) {
            $infoKey = array_search($id, array_column($cacheInfo, 'id'));
            $cacheInfo[$infoKey]['status'] = 'true';
            cachecore::store('system_notice_data', $cacheInfo, 1800);
            $noticeMdl->update(['status' => 'true'], array('id' => $id));
        }
        $render->pagedata['info'] = $data;
        
        return $render->fetch('admin/system/detail_basic.html');
    }
    
    var $column_msg = '信息';
    var $column_msg_width = "300";
    
    function column_msg($row)
    {
        if (is_array($info = json_decode($row[$this->col_prefix . 'msg'], true))) {
            return $info['info'];
        } else {
            return $row[$this->col_prefix . 'msg'];
        }
    }
}