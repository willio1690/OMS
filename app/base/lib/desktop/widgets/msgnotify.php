<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class base_desktop_widgets_msgnotify implements desktop_interface_widget
{
    var $order = 7;
    
    function __construct($app)
    {
        $this->app    = $app;
        $this->render = new base_render(app::get('ome'));
    }
    
    function get_title()
    {
        return '系统消息';
    }
    
    function get_html()
    {
        $render = $this->render;
        $info   = array();
        
        $info = $this->fetchDataFromCache();
        if (empty($info)) {
            $noticeMdl = app::get('base')->model('rpcnotify');
            $shopMdl   = app::get('ome')->model('shop');
            $shopList  = $shopMdl->getList('name,node_id', array('node_id|noequal' => ''));
            if ($shopList) {
                $shopNodeList = array_column($shopList, null, 'node_id');
            }
            
            $list = $noticeMdl->getList('*', array(), 0, 6, 'notifytime DESC');
            foreach ($list as $key => $value) {
                $notice = json_decode($value['msg'], true);
                if (isset($notice['dead_line']) && $shopNodeList) {
                    //店铺名称在数据保存是已拼接，店铺解绑后这里在通过node_id查不到数据
//                    $notice['info']       = $shopNodeList[$notice['node_id']]['name'] . '-' . $notice['info'];
                    $notice['notifytime'] = date('Y-m-d H:i:s', $value['notifytime']);
                    $notice['status']     = $value['status'];
                    $notice['callback']   = '';
                    $notice['id']         = $value['id'];
                    $info[]               = $notice;
                }
            }
            
            if ($info) {
                $look = [
                    'info'     => '查看更多...',
                    'status'   => 'look',
                    'callback' => 'index.php?app=desktop&ctl=rpcnotify&act=index',
                ];
                array_push($info, $look);
                $this->saveDataToCache($info);
            }
        }

        $render->pagedata['info'] = $info;
        $html = $render->fetch('desktop/widgets/system_notice.html', 'base');
        return $html;
    }
    
    function fetchDataFromCache()
    {
        return cachecore::fetch('system_notice_data');
    }
    
    function saveDataToCache($info)
    {
        cachecore::store('system_notice_data', $info, 1800);
    }
    
    function get_className()
    {
        return "";
    }
    
    function get_width()
    {
        return "l-2";
    }
    
}

?>