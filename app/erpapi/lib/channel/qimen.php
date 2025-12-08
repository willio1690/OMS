<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_channel_qimen extends erpapi_channel_abstract
{
    public $channel = [];
    
    /**
     * 初始化
     *
     * @param mixed $node_id 店铺ID
     * @param mixed $shop_id ID（默认此字段不会给值）
     * @return mixed 返回值
     */
    public function init($node_id, $shop_id=null)
    {
        // 店铺ID
        if($_REQUEST['shop_id'] && $shop_id){
            $filter = array('shop_id'=>$shop_id);
            $shopInfo = $this->get_shop($filter);
            if (empty($shopInfo)){
                return false;
            }
            
            // 获取奇门聚石塔内外互通渠道信息
            $channelInfo = kernel::single('channel_channel')->getQimenJushitaErp();
            if (empty($channelInfo)){
                return false;
            }
            
            // merge
            $shopInfo['app_key'] = $channelInfo['app_key']; // 调用方appKey(OMS系统)
            $shopInfo['target_app_key'] = $channelInfo['node_id']; // 实现方appKey(矩阵系统)
            $shopInfo['secret_key'] = $channelInfo['secret_key']; // 密钥
            
            $this->channel = $shopInfo;
            
            return true;
        }
        
        // app_key
        $app_key = $_REQUEST['target_appkey'];
        if(empty($app_key)){
            return false;
        }
        
        // 获取奇门聚石塔内外互通渠道信息
        $channelInfo = kernel::single('channel_channel')->getQimenJushitaErp($app_key);
        if (empty($channelInfo)){
            return false;
        }
        
        // check
        if (empty($channelInfo['app_key']) || empty($channelInfo['secret_key'])){
            return false;
        }
        
        // shop
        $this->channel = $channelInfo;
        
        // Setting
        $this->__adapter = 'matrix'; // 路由（此字段有值，会调用：$this->_check_sign($channel_class, $params) 验签）
        //$this->__platform = 'qimen'; // 请求平台
        
        return true;
    }
    
    private function get_shop($filter)
    {
        static $shops;
        
        $key = sprintf('%u',crc32(serialize($filter)));
        
        if ($shops[$key]) return $shops[$key];
        
        $shops_detail = app::get('ome')->model('shop')->dump($filter);
        if ($shops_detail['config']){
            $shops_detail['config'] = @unserialize($shops_detail['config']);
        }
        
        if (is_string($shops_detail['addon']) && $shops_detail['addon']){
            $shops_detail['addon'] = @unserialize($shops_detail['addon']);
        }
        
        // 全民分销默认1.0
        if($shops_detail['node_type'] == 'shopex_fy'){
            $shops_detail['api_version'] = '1.0';
        }
        
        // bbc
        if ($shops_detail['node_type'] == 'bbc') {
            $shops_detail['matrix_api_v'] = '2.0';
        }
        
        $shops[$key] = $shops_detail;
        
        return $shops[$key];
    }
}