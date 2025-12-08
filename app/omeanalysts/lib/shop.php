<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeanalysts_shop{

    /**
     * 根据平台类型返回对应店铺id
     * @return 
     */
    public function getShopList(){
        $shopModel = app::get('ome')->model('shop');
        $shopList = $shopModel->getList('shop_id,shop_type');

        $shops = array();
        foreach($shopList as $shop){
            $shops[$shop['shop_type']][] = $shop['shop_id'];
        }

        return $shops;
    }

    public function getShopDetail($shop_id){
        
        static $shop;
        if ($shop[$shop_id]) return $shop[$shop_id];
        $shopModel = app::get('ome')->model('shop');
        $shop_detail = $shopModel->dump(array('shop_id'=>$shop_id),'shop_type');

        $shoptype = ome_shop_type::get_shop_type();

        if($shop_detail['shop_type']){
            return $shoptype[$shop_detail['shop_type']];
        }else{
            return '-';
        }
    }

    /**
     * 获取ShopType
     * @param mixed $shop_type shop_type
     * @return mixed 返回结果
     */
    public function getShopType($shop_type){
        $shoptype = ome_shop_type::get_shop_type();
        return $shoptype[$shop_type];
    }
}


?>