<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Smart平台路由
 *
 * @author wangbiao@shopex.cn
 * @version 2024.04.23
 */
class erpapi_channel_smart extends erpapi_channel_abstract
{
    public $channel;
    public $wms;
    
    private static $wms_mapping = array(
        'jd_wms'       => '360buy',
        'sf_wms'       => 'sf',
    );
    
    /**
     * 初始化
     * @param mixed $node_id ID
     * @param mixed $channel_id ID
     * @return mixed 返回值
     */

    public function init($node_id, $channel_id)
    {
        $channelMdl = app::get('channel')->model('channel');
        $adapterModel = app::get('channel')->model('adapter');
        
        //channel
        $filter = $channel_id ? array('channel_id'=>$channel_id) : array('node_id'=>$node_id);
        $filter['channel_type'] = 'smart';
        $channelInfo = $channelMdl->dump($filter, '*');
        if (!$channelInfo) {
            return false;
        }
        
        $this->__adapter = 'matrix';
        
        //所属平台
        $this->__platform = '';
        if(!in_array($channelInfo['node_type'], array('publicwms', 'selfwms'))){
            $this->__platform = $channelInfo['node_type'];
        }
        
        //config
        $channelInfo['config'] = @unserialize($channelInfo['config']);
        
        //adapter
        $adapter = $adapterModel->dump(array('channel_id' => $channelInfo['channel_id']));
        $adapter['config'] = @unserialize($adapter['config']);
        
        $channelInfo['adapter'] = $adapter;
        
        $this->wms = $channelInfo;
        $this->channel = $channelInfo;
        
        return true;
    }
}