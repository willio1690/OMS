<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 淘宝o2o接口通路定义类
 * 20160824
 * @author wangjianjun<wangjianjun@shopex.cn>
 * @version 0.1
 */
class erpapi_channel_tbo2o extends erpapi_channel_abstract 
{
    public $tbo2o;

    /**
     * 初始化
     * @param mixed $node_id ID
     * @param mixed $id ID
     * @return mixed 返回值
     */

    public function init($node_id,$id){
        //$id外层传true无视这个参数
        //获取主店铺信息
        $tbo2o_shop = kernel::single('tbo2o_common')->getTbo2oShopInfo();
        $mdlOmeShop = app::get('ome')->model('shop');
        $rs_ome_shop = $mdlOmeShop->dump(array("shop_id"=>$tbo2o_shop["shop_id"]),"node_id");
        $this->tbo2o = array(
            "node_id" => $rs_ome_shop["node_id"],
        );
        return true;
    }
}