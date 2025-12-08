<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @Author: xueding@shopex.cn
 * @Datetime: 2022/4/11
 * @Describe: 外部erp（优仓）
 */
class erpapi_channel_dchain extends erpapi_channel_abstract
{
    public $channel;
    
    /**
     * 初始化
     * @param mixed $node_id ID
     * @param mixed $shop_id ID
     * @return mixed 返回值
     */

    public function init($node_id,$shop_id)
    {
        $filter = $shop_id ? array('shop_id'=>$shop_id) : array('node_id'=>$node_id);
        
        $shop = $this->get_shop($filter);
        
        if (!$shop || !$shop['node_id']) return false;
        
        if (!app::get('channel')->model('channel')->db_dump(array('node_id'=>$shop['node_id'],'channel_type'=>'dchain'))) {
            return false;
        }
        
        $this->channel = $shop;
        
        $this->__adapter = 'matrix';
        
        $this->__platform = $shop['node_type'];
        
        
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
        
        $shops[$key] = $shops_detail;
        return $shops[$key];
    }
}