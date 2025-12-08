<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 天猫订阅消息
 * wangjianjun 20181107
 * @version 0.1
 */
class erpapi_channel_tmcgroup extends erpapi_channel_abstract 
{
    public $tmcgroup;

    /**
     * 初始化
     * @param mixed $node_id ID
     * @param mixed $shop_id ID
     * @return mixed 返回值
     */

    public function init($node_id,$shop_id){
        $mdl_shop = app::get('ome')->model('shop');
        $rs_shop = $mdl_shop->dump(array("shop_id"=>$shop_id),"node_id,name");
        $this->tmcgroup = $rs_shop;
        return true;
    }
    
}