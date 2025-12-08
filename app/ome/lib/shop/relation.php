<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_shop_relation{

    /**
     * 店铺绑定
     */
    public function bind($shop_id){
        //同步店铺支付方式
        $payFuncObj = kernel::single("ome_payment_func");
        if(method_exists($payFuncObj, 'sync_payments')){
            $payFuncObj->sync_payments($shop_id);
        }
        if($shop_id){
            $mdl_ome_shop = app::get('ome')->model('shop');
            $rs_shop = $mdl_ome_shop->dump($shop_id);
            if($rs_shop["node_id"] && $rs_shop['node_type'] == 'taobao'){
                $sdf = array();
                //kernel::single('invoice_event_trigger_einvoice')->bindTbTmcGroup($shop_id,$sdf);
            }
        }
        return true;
    }

    /**
     * 解除店铺绑定
     */
    public function unbind($shop_id){
        //删除店铺支付方式
        $payFuncObj = kernel::single("ome_payment_func");
        if(method_exists($payFuncObj, 'del_payments')){
            $payFuncObj->del_payments($shop_id);
        }

        //删除库存同步日志
        $stockLogObj = app::get('ome')->model('api_stock_log');
        $stockLogObj->delete(array('shop_id'=>$shop_id));
        return true;
    }
}
