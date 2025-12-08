<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_sales_data_type_gift
{
    /**
     * doTrans
     * @param mixed $obj obj
     * @return mixed 返回值
     */
    public function doTrans($obj)
    {
        $deliveryObj = app::get('ome')->model('delivery');
        $delivery_items_detailObj = app::get('ome')->model('delivery_items_detail');

        //基础物料扩展数据
        $basicMaterialExtObj    = app::get('material')->model('basic_material_ext');

        $delivery_id = $obj['delivery_id'];

        //[拆单]获取订单对应所有发货单delivery_id
        $orderSplitLib    = kernel::single('ome_order_split');
        $split_seting     = $orderSplitLib->get_delivery_seting();
        if($split_seting && !empty($obj['order_id'])){
            $order_id       = $obj['order_id'];
        }
        
        //获取平台优惠明细
        $couponListDetail = kernel::single('ome_order_coupon')->getOrderCoupon($obj['order_id']);
        $platformAmount = 0;
        if ($couponListDetail && current($couponListDetail)['source'] == 'push' && in_array(current($couponListDetail)['shop_type'], array('360buy'))) {
            $platformAmount = isset($couponListDetail[$obj['oid']]['calcPlatformDiscountsTotalAmount']) ? $couponListDetail[$obj['oid']]['calcPlatformDiscountsTotalAmount'] : 0;
        }
        
        $items = $obj['order_items'];
        foreach($items as $k =>$item)
        {
            //物料规格
            $material_ext = $basicMaterialExtObj->dump(array('bm_id'=>$item['product_id']), 'bm_id, specifications');
            
            //sale_items
            $sale_item[$k] = array(
                'iostock_id'=>'',
                'product_id' => $item['product_id'],
                'bn' => $item['bn'],
                'name' => $item['name'],
                'spec_name'=> $material_ext['specifications'],
                'pmt_price' => $item['pmt_price'],
                'orginal_price' => $item['price'],
            	'price' => $item['price'],
                'nums' => $item['quantity'],
            	'sale_price' => $item['sale_price'],
                'cost'=> $item['cost'],
                'obj_id' => $obj['obj_id'],
                'obj_type'=>'gift',
                'sales_material_bn'=>$obj['bn'],
                's_type' => $obj['s_type'],
                'oid' => $obj['oid'],
                'order_item_id' => $item['item_id'],
                'platform_amount' => $platformAmount,
                'addon' => json_encode(['shop_goods_id' => $item['shop_goods_id'], 'shop_product_id' => $item['shop_product_id']],JSON_UNESCAPED_UNICODE),
            );

            $delivery_items_detail_info = $delivery_items_detailObj->dump(array('order_id'=>$item['order_id'],'order_item_id'=>$item['item_id'],'order_obj_id'=>$item['obj_id'],'delivery_id'=>$delivery_id));
            $sale_item[$k]['item_detail_id'] = $delivery_items_detail_info['item_detail_id'];

            $delivery_info = $deliveryObj->dump(array('delivery_id'=>$delivery_items_detail_info['delivery_id']),'branch_id');
            $sale_item[$k]['branch_id'] = $delivery_info['branch_id'];
        }
        
        return $sale_item;
    }
}
