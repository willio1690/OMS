<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_pos_response_refund extends erpapi_shop_response_refund {

    
    protected function _formatAddParams($sdf)
    {

        if($sdf['refund_items']){
            if(is_string($sdf['refund_items'])){
                $refund_items = json_decode($sdf['refund_items'],true);
            }else{
                $refund_items = $sdf['refund_items'];
            }
        }
        
        $params = parent::_formatAddParams($sdf);
        if ($refund_items) {
            
            $order = app::get('ome')->model('orders')->db_dump(['order_bn'=>$params['order_bn']],'order_id');
            $objList = app::get('ome')->model('order_objects')->getList('*',['order_id'=>$order['order_id']]);
            $order_items = app::get('ome')->model('order_items')->getList('*',['order_id'=>$order['order_id']]);

            if ($order_items){
                $tmp_items = array();
                foreach ($order_items as $i_key=>$i_val){
                    $tmp_items[$i_val['obj_id']][] = $i_val;
                }
                $order_items = NULL;
            }
    
            if ($objList){
                foreach ($objList as $o_key=>&$o_val){
                    $o_val['order_items'] = $tmp_items[$o_val['obj_id']];
                }
            }
            $objList = array_column($objList,null,'oid');
    
            $productData = array();
           
            foreach ($refund_items as $key => $val) {
                if ($objList[$val['oid']]) {
                    $obj = $objList[$val['oid']];
                    foreach ($obj['order_items'] as $itemKey => $itemVal) {
                        $item = [];
                        $item['order_item_id'] = $itemVal['item_id'];
                        $item['num'] = $val['number'] ?? $itemVal['nums'];
                        $item['product_id'] = $itemVal['product_id'];
                        $item['bn'] = $val['sku_bn'] ?? $itemVal['bn'];
                        $item['name'] = $val['sku_name'] ?? $itemVal['name'];
                        $item['price'] = $val['price'] ?? $itemVal['sale_price'];
                        $item['oid'] = $val['oid'];
                        $item['item_type'] = $itemVal['item_type'];
                        $item['obj_id'] = $itemVal['obj_id'];
                        $productData[] = $item;
                    }
                }
            }
            if ($productData) {
                $params['product_data'] = $productData;

                $params['bn']  = implode(',', array_column((array)$productData, 'bn'));
                $params['oid']  = implode(',', array_column((array)$productData, 'oid'));
                $params['obj_id']  = implode(',', array_column((array)$productData, 'obj_id'));

            }
        }
       
        return $params;
    }
}
