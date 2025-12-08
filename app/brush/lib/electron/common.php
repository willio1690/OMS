<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2017/7/14
 * @describe 刷单电子面单公用
 */
class brush_electron_common extends brush_electron_abstract{
    private $batchWaybillChannel = array('taobao', 'pdd');
    /**
     * deliveryToSdf
     * @param mixed $arrDelivery arrDelivery
     * @return mixed 返回值
     */

    public function deliveryToSdf($arrDelivery) {
        $sdf = parent::deliveryToSdf($arrDelivery);
        $num = $this->bufferGetWaybill();
        if($num > 1 || in_array($this->channel['channel_type'], $this->batchWaybillChannel)) {
            $shop = $this->getChannelExtend();
            $deliveryExtend = array();
            foreach($arrDelivery as $delivery) {
                $deliveryItems = $this->getDeliveryItems($delivery['delivery_id']);//发货单明细
                $tmpDelivery = $delivery;
                $tmpDelivery['delivery_item'] = $deliveryItems;
                $package = array();
                foreach ($deliveryItems as $item ) {
                    $product_name = $item['product_name'] ? $item['product_name'] : '商品名称';
                    if(isset($product_name[120])) {
                        $product_name = mb_substr($product_name, 0, 120, 'utf8');
                    }
                    $package[] = array('item_name'=>$product_name,'count'=>$item['number']);
                }
                $tmpDelivery['package_items'] = $package;
                $deliveryExtend[] = $tmpDelivery;
            }
            $primary_bn = uniqid();
            $sdf['primary_bn'] = $primary_bn;
            $sdf['shop']       = $shop;
            $sdf['delivery']   = $deliveryExtend;
        } else {
            $delivery = $arrDelivery[0];
            $shop = $this->getChannelExtend();
            if(empty($shop)){
                $shop_obj = app::get('ome')->model('shop');
                $_shop = $shop_obj->getList('shop_id,default_sender,mobile,tel,area,name as shop_name,addr as address_detail',array('shop_id' => $delivery['shop_id']));
                $shop  = $_shop[0];
                $addr_arr = explode(':',$shop['area']);
                $address = explode('/', $addr_arr[1]);
                $shop['province'] = $address[0];
                $shop['city'] = $address[1];
                $shop['area'] = $address[2];
            }
            $deliveryItems = $this->getDeliveryItems($delivery['delivery_id']);
            $sdf['primary_bn']    = $delivery['delivery_bn'];
            $sdf['delivery']      = $delivery;
            $sdf['shop']          = $shop;
            $sdf['delivery_item'] = $deliveryItems;
            $sdf['corp']          = $this->getDlyCorp($delivery['logi_id']);
        }
        return $sdf;
    }
}