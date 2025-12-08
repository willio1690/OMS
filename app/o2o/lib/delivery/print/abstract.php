<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class o2o_delivery_print_abstract{

    protected function covertNullToString(&$items) {
        foreach ($items as $k => &$v) {
            if ($v === null) {
                $v = "";
            }
            elseif (is_array($v)) {
                $this->covertNullToString($v);
            }
            elseif (is_bool($v)) {
                $v = $v === false ? 'false' : 'true';
            }
            else {
                $v = strval($v);
            }
        }
        return $items;
    }

    
    /**
     * 打印新版本销售模式数据结构重组
     *
     * @return void
     * @author chenping<chenping@shopex.cn>
     **/
    protected function formatSaleStyleData($delivery)
    {
        //[拆单]发货单明细详情
        $deliItemDetailModel = app::get('ome')->model('delivery_items_detail');
        $deliItemDetailList = $deliItemDetailModel->getList('*',array('delivery_id'=>$delivery['ome_delivery_id']));
        
        $delivery_detail    = array();
        foreach ($deliItemDetailList as $key => $val)
        {
            $order_id   = $val['order_id'];
            $obj_id     = $val['order_obj_id'];
            $item_id    = $val['order_item_id'];
            
            $delivery_detail[$order_id][$obj_id][$item_id]  = $val;
        }

        //是否有保质期条码信息
        if(isset($delivery['expire_bns'])){
            //取wms发货单item_id与ome_item_id的对应关系
            $wapDlyItemsObj = app::get('wap')->model('delivery_items');
            $item_ids_arr = $wapDlyItemsObj->getList('item_id,outer_item_id',array('delivery_id'=>$delivery['delivery_id']));
            foreach($item_ids_arr as $item_ids){
                $bind_item_ids[$item_ids['item_id']] = $item_ids['outer_item_id'];
            }

            foreach($delivery['expire_bns'] as $ekey => $expire_bn_info){
                if(isset($bind_item_ids[$expire_bn_info['item_id']])){
                    $item_expire_bns[$bind_item_ids[$expire_bn_info['item_id']]][] = $expire_bn_info['expire_bn'];
                }
            }
        }

        //仅定义当前发货单明细信息
        $format_delivery_items = array();
        foreach ($delivery['delivery_items'] as $key => $value) {
            if(isset($item_expire_bns[$value['item_id']])){
                $value['expire_bn'] = implode("\n",$item_expire_bns[$value['item_id']]);
            }

            $format_delivery_items[$value['item_id']] = $value;
        }

        //仅定义当前发货单相关订单obj信息
        $format_order_objects = array();
        foreach ($delivery['orders'] as $key => $order) {
            foreach($order['order_objects'] as $ook => $obj){
                
                //[拆单]计算PKG商品购买量
                if($obj['obj_type'] == 'pkg')
                {
                    $order_id       = $order['order_id'];
                    $obj_id         = $obj['obj_id'];
                    $old_quantity   = $obj['quantity'];
                    
                    foreach ($obj['order_items'] as $oik => $item)
                    {
                        if($item['nums'] && !empty($delivery_detail[$order_id][$obj_id][$item['item_id']]['number']))
                        {
                            $old_item_number    = $item['nums'];
                            $dly_quantity       = $delivery_detail[$order_id][$obj_id][$item['item_id']]['number'];
                            $dly_quantity       = intval($dly_quantity / ($old_item_number / $old_quantity));
                           
                            $obj['quantity']    = $dly_quantity ? $dly_quantity : $obj['quantity'];//发货单对应PKG购买量
                            break;
                        }
                    }
                }
                
                unset($obj['order_items']);
                $format_order_objects[$obj['obj_id']] = $obj;
            }
        }

        //仅定义当前发货单相关订单item信息
        $format_order_items = array();
        foreach ($delivery['orders'] as $key => $order) {
            foreach($order['order_objects'] as $ook => $obj){
                foreach ($obj['order_items'] as $oik => $item)
                {
                    //计算item_product商品购买量
                    $order_id   = $order['order_id'];
                    $obj_id     = $obj['obj_id'];
                    $item_id    = $item['item_id'];
                    $dly_number = $delivery_detail[$order_id][$obj_id][$item_id]['number'];
                    
                    $item['number']         = $dly_number ? $dly_number : $item['nums'];
                    $item['sale_price']     = floatval(($item['sale_price']/$item['nums'])*$item['number']);//实际价格
                    
                    unset($item['name'],$item['addon']);
                    $format_order_items[$item['item_id']] = $item;
                }
            }
        }
        
        $salesMObj = app::get('material')->model('sales_material');
        //是否打印前端商品名称
        $deliCfgLib = kernel::single('wms_delivery_cfg');
        $sku = '';
        $is_print_front = (1 == $deliCfgLib->getValue('wms_delivery_is_printdelivery',$sku)) ? true : false;

        $data = array();
        foreach ($deliItemDetailList as $key => $value) {
            $order_object = $format_order_objects[$value['order_obj_id']];
            $order_item = $format_delivery_items[$value['delivery_item_id']];
            if (!$order_item) { continue; }

            $order_item = array_merge($order_item,$format_order_items[$value['order_item_id']]);

            if (isset($data[$order_object['obj_type']][$order_object['bn']])) {
                $obj_id_list = $data[$order_object['obj_type']][$order_object['bn']]['obj_id_list'];
                if (!in_array($order_object['obj_id'], $obj_id_list)) {
                    $obj_id_list[] = $order_object['obj_id'];
                    $data[$order_object['obj_type']][$order_object['bn']]['obj_id_list'] = $obj_id_list;
                    $data[$order_object['obj_type']][$order_object['bn']]['quantity'] += $order_object['quantity'];
                    $data[$order_object['obj_type']][$order_object['bn']]['amount'] += $order_object['amount'];
                    $data[$order_object['obj_type']][$order_object['bn']]['sale_price'] += $order_object['sale_price'];
                    $data[$order_object['obj_type']][$order_object['bn']]['pmt_price'] += $order_object['pmt_price'];
                }

                if (isset($data[$order_object['obj_type']][$order_object['bn']]['order_items'][$value['bn']])) {
                    $data[$order_object['obj_type']][$order_object['bn']]['order_items'][$value['bn']]['number'] += $order_item['number'];
                    $data[$order_object['obj_type']][$order_object['bn']]['order_items'][$value['bn']]['pmt_price'] += $order_item['pmt_price'];
                    $data[$order_object['obj_type']][$order_object['bn']]['order_items'][$value['bn']]['sale_price'] += $order_item['sale_price'];
                    $data[$order_object['obj_type']][$order_object['bn']]['order_items'][$value['bn']]['amount'] += $order_item['amount'];
                } else {
                    $data[$order_object['obj_type']][$order_object['bn']]['order_items'][$value['bn']] = $order_item;
                }
            } else {
                $order_object['obj_id_list'][] = $order_object['obj_id'];
                $data[$order_object['obj_type']][$order_object['bn']] = $order_object;
                $data[$order_object['obj_type']][$order_object['bn']]['order_items'][$value['bn']] = $order_item;

                if($order_object['obj_type']=='pkg'){
                    $pkg = $salesMObj->getlist('sales_material_name',array('sales_material_bn'=>$order_object['bn'],'type'=>2),0,1);
                    if (!$is_print_front) {
                        $data[$order_object['obj_type']][$order_object['bn']]['product_name'] = $pkg[0]['sales_material_name'];
                        $data[$order_object['obj_type']][$order_object['bn']]['name'] = $pkg[0]['sales_material_name'];
                    }else{
                        $data[$order_object['obj_type']][$order_object['bn']]['product_name'] = $order_object['name'];
                    }
                }
            }
        }      
        $delivery['delivery_items'] = $data;

        return $delivery;
    }
}