<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

#智选物流
class erpapi_channel_exrecommend extends erpapi_channel_abstract {

    /**
     * 初始化
     * @param mixed $channel_id ID
     * @param mixed $shop_id ID
     * @return mixed 返回值
     */
    public function init($channel_id,$shop_id){ 
        $obj_shop = app::get('ome')->model('shop');
        $shop_info = $obj_shop->getList('node_id',array("shop_id"=>$shop_id));
        #如果存在店铺，就一定是菜鸟智选,因为菜鸟智选和店铺关联
        if($shop_info){
            $this->__adapter = 'matrix';
            $this->__platform = 'taobao';
            //$this->exrecommend["to_node_id"] = $shop_info[0]["node_id"];
            $this->exrecommend["to_node_id"] = '1033373233';
        }else{
            $this->__adapter = 'matrix';
            $this->__platform = 'hqepay';
            $this->exrecommend["to_node_id"] = $shop_id;
        }  
        return true;
    }
}