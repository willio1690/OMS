<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Date: 2023/4/20
 * @Describe: 微信视频号
 */

class ome_event_trigger_shop_data_delivery_wxshipin extends ome_event_trigger_shop_data_delivery_common
{
    public function get_sdf($delivery_id)
    {
        $this->__sdf = parent::get_sdf($delivery_id);
        
        if ($this->__sdf) {
            $this->_get_split_sdf($delivery_id);
            // 如果没有全部发货
            if ($this->__sdf['orderinfo']['ship_status'] != '1') {
                return array();
            }
            
            $order = $this->__delivery_orders[$delivery_id];
            
            $orderInfo =  app::get('ome')->model('orders')->dump(array('order_id'=>$order['order_id']) , '*' , array('order_objects' => array('*',array('order_items' => array('*')))));
            
            $expresses = [];
            foreach ($orderInfo['order_objects'] as $key => $object) {
                if (empty($object['oid'])) {
                    continue;
                }
                foreach ($object['order_items'] as $k => $item) {
                    // 过滤掉已经删除的明细
                    if (isset($item['delete']) && $item['delete'] == 'true') {
                        continue;
                    }
                    if ($item['item_type'] == 'pkg') {
                        $shop_goods_id = $object['shop_goods_id'];
                    } else {
                        $shop_goods_id = $item['shop_goods_id'];
                    }
                    $quantity        = $object['quantity'];
                    $logi_no         = $this->__sdf['logi_no'];
                    $logi_type       = $this->__sdf['logi_type'];
                    $logi_name       = $this->__sdf['logi_name'];
                    $shop_product_id = $item['shop_product_id'];
                    
                    $newKey                                                  = $item['oid'] . '_' . $item['shop_product_id'];
                    $expresses[$newKey]['nums']                              = $quantity;
                    $expresses[$newKey]['packages'][$newKey]['logistics_no'] = $logi_no;
                    $expresses[$newKey]['packages'][$newKey]['company_code'] = $logi_type;
                    $expresses[$newKey]['packages'][$newKey]['company_name'] = $logi_name;
                    $expresses[$newKey]['packages'][$newKey]['product_id']   = $shop_goods_id;
                    $expresses[$newKey]['packages'][$newKey]['sku_id']       = $shop_product_id;
                    $expresses[$newKey]['packages'][$newKey]['oid']          = $object['oid'];
                    $expresses[$newKey]['packages'][$newKey]['product_cnt']  += $quantity;
                    if ($item['item_type'] == 'pkg') {
                        break;
                    }
                }
            }
            
            $packages = [];
            foreach ($expresses as $oid => $express) {
                $packages[] = current($express['packages']);
            }
            
            if (!$packages) {
                return array();
            }
            
            $this->__sdf['goods']    = $packages;
        }
        
        return $this->__sdf;
    }
}
