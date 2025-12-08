<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2016-03-11
 * @describe 店铺货品冻结库存
*/
class erpapi_shop_response_plugins_order_crm extends erpapi_shop_response_plugins_order_abstract
{

    /**
     * convert
     * @param erpapi_shop_response_abstract $platform platform
     * @return mixed 返回值
     */

    public function convert(erpapi_shop_response_abstract $platform)
    {
        $crm = array();
        
        //check过滤经销商模拟创建OMS订单
        if($platform->_newOrder['delivery_mode'] == 'shopyjdf'){
            return $crm;
        }
        
        if ($platform->_tgOrder && (isset($platform->_newOrder['total_amount']) || isset($platform->_newOrder['order_objects']) || isset($platform->_newOrder['consignee']) ) ) {
            $crm = array(
                'order_id'=>$platform->_tgOrder['order_id'],
            );
        }
        
        if(!$platform->_tgOrder) {
            if(app::get('ome')->getConf('gift.order.create.deal') == 'true') {
                $crm['create_deal_gift'] = true;
            }
        }
        
        return $crm;
    }

    /**
     * 订单完成后处理
     **/
    public function postCreate($order_id,$crm)
    {
        if($crm['create_deal_gift']) {
            $msg = '';
            kernel::single('ome_preprocess_crm')->process($order_id,$msg);
            kernel::single('omeauto_auto_hold')->process($order_id);
        }
    }

    /**
     * 更新后操作
     *
     * @return void
     * @author 
     **/
    public function postUpdate($order_id,$crm)
    {
        $orderItemObj   = app::get('ome')->model("order_items");
        $orderObjectObj = app::get('ome')->model("order_objects");
        $Obj_preprocess = app::get('ome')->model('order_preprocess');
    
        // 删除CRM相关记录记录(shop_goods_id=-1是， CRM赠品类型)
        $orderItemObj->delete(array('order_id'=>$order_id,'shop_goods_id'=>'-1','item_type' => 'gift'));
        $orderObjectObj->delete(array('order_id'=>$order_id,'shop_goods_id'=>'-1','obj_type' => 'gift'));
        $Obj_preprocess->delete(array('preprocess_order_id'=>$order_id,'preprocess_type'=>'crm'));

        // 重新获取CRM赠品
        kernel::single('ome_preprocess_crm')->process($order_id,$msg,1);
    }
}