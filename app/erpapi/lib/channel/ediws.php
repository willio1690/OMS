<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_channel_ediws extends erpapi_channel_abstract
{
    public $shop;
    
    /**
     * 初始化
     * @param mixed $node_id ID
     * @param mixed $shop_id ID
     * @return mixed 返回值
     */
    public function init($node_id,$shop_id)
    {

        $filter = $shop_id ? array('shop_id'=>$shop_id) : array('node_id'=>$node_id);
        
        $shop = app::get('ome')->model('shop')->dump($filter,'config');

       
        if ($shop['config']){
            $shop['config'] = @unserialize($shop['config']);
        }

        $this->edi = $shop;

        return true;
    }
}