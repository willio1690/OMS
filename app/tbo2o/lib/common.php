<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/*
 * 淘宝全渠道公共类
 */
class tbo2o_common{
    
   //获取阿里全渠道主店铺信息
    /**
     * 获取Tbo2oShopInfo
     * @return mixed 返回结果
     */
    public function getTbo2oShopInfo(){
       $mdlTbo2oShop = app::get('tbo2o')->model('shop');
       $rs_tbo2o_shop = $mdlTbo2oShop->getList("*",array(),0,1);
       return $rs_tbo2o_shop[0];
   }
   
   //获取阿里全渠道服务端信息
    /**
     * 获取Tbo2oServerInfo
     * @return mixed 返回结果
     */
    public function getTbo2oServerInfo(){
       $mdlO2oServer = app::get('o2o')->model('server');
       $rs_o2o_server = $mdlO2oServer->dump(array("type"=>"taobao"));
       return $rs_o2o_server;
   }
   
}
