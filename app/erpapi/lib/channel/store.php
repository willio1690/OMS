<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 门店接口通道类
 *
 * @author xiayuanjun@shopex.cn
 * @version 0.1
 *
 */
class erpapi_channel_store extends erpapi_channel_abstract
{
    public $store;
    
    /**
     * 初始化
     * @param mixed $node_id ID
     * @param mixed $channel_id ID
     * @return mixed 返回值
     */

    public function init($node_id,$channel_id)
    {
        //获取server_id
        $mdlO2oStore = app::get('o2o')->model('store');
        $rs_store_info = $mdlO2oStore->dump(array("store_id"=>$channel_id),"server_id,store_bn,store_id");
        //获取门店服务端配置信息
        //
        $filter    = $rs_store_info['server_id'] ? array('server_id' => $rs_store_info['server_id']) : array('node_id' => $node_id);
        $serverObj = app::get('o2o')->model('server');
        $store = $serverObj->dump($filter);

        if (!$store) {
            return false;
        }
        $store['config'] = @unserialize($store['config']);
        $store['store_bn'] = $rs_store_info['store_bn'];
        $store['store_id'] = $rs_store_info['store_id'];
        if($store['type'] == 'wap'){
            $this->__adapter = '';
            $this->__platform = 'wap';
        }elseif($store['type'] == 'taobao'){
            //根据线下服务端配置识别当前的路径
            $this->__adapter = 'matrix';
            $this->__platform = 'taobao';
            
            //目前一套OMS只管控一家淘宝或天猫店铺 有且只有一条配置记录 获取node_id主店铺
            $tbo2o_shop = kernel::single('tbo2o_common')->getTbo2oShopInfo();
            $mdlOmeShop = app::get('ome')->model('shop');
            $rs_ome_shop = $mdlOmeShop->dump(array("shop_id"=>$tbo2o_shop["shop_id"]),"node_id");
            $store["node_id"] = $rs_ome_shop["node_id"];
        }elseif ($store['type'] == 'openapi') {
         

            $this->__adapter  = 'openapi';
            $this->__platform = $store['node_type'];
           
        }

        $this->store = $store;

        return true;
    }
}