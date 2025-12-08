<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class invoice_order_front_b2c extends invoice_order_front_abstract {

    #获取主表特殊信息
    public function getMain($main) {
        $orderField = 'order_bn,total_amount,shop_id,shop_type,cost_freight,is_tax,org_id';
        $order = app::get('ome')->model('orders')->db_dump(['order_id'=>$main['source_id']], $orderField);
        if(empty($order)) {
            $order = app::get('archive')->model('orders')->db_dump(['order_id'=>$main['source_id']], $orderField);
        }
        $shop = app::get('ome')->model('shop')->db_dump(['shop_id'=>$order["shop_id"]], 'delivery_mode');
        if($shop['delivery_mode'] == 'jingxiao') {
            return [];
        }
        $main['is_status'] = isset($main['is_status']) ? $main['is_status'] : ($order['is_tax'] == 'true' ? '0' : '2'); 
        $main['source_bn'] = $order['order_bn'];
        $main['shop_id'] = $order['shop_id'];
        $main['shop_type'] = $order['shop_type'];
        $main['amount'] = $main['amount'] ? : $order['total_amount'];
        $main['cost_freight'] = $order['cost_freight'];
        $main['org_id'] = $order['org_id'];
        //对pos判断btq trade 不在列表里显示
        if($main['shop_type'] == 'pekon'){
            $stores = app::get('o2o')->model('store')->db_dump(array('shop_id'=>$main['shop_id']),'store_sort');
            if(in_array($stores['store_sort'],array('BTQ','Trade'))){
                $main['disabled'] = 'true';
            }
        }
        return $main;
    }
    #获取明细信息
    public function getItems($main) {
        $orderId = $main['source_id'];
        $objField = 'obj_id,goods_id,bn,name,divide_order_fee,quantity,`delete`';
        $rows = app::get('ome')->model('order_objects')->getList($objField, ['order_id'=>$orderId]);
        $objApp = 'ome';
        if(empty($rows)) {
            $objApp = 'archive';
            $rows = app::get('archive')->model('order_objects')->getList($objField, ['order_id'=>$orderId]);
        }
        $itemField = 'item_id,obj_id,nums,return_num,product_id,bn,name,divide_order_fee,return_num,`delete`';
        $items = app::get($objApp)->model('order_items')->getList($itemField, ['order_id'=>$orderId]);
        //开票明细开基础物料
        if (app::get('ome')->getConf('ome.invoice.material.type') == 'sales') {
            $return = [];
            foreach($items as $v) {
                $returnRadio = $v['return_num'] / $v['nums'];
                if($return[$v['obj_id']] && $return[$v['obj_id']] <= $returnRadio) {
                    continue;
                }
                $return[$v['obj_id']] = $returnRadio;
            }
            $ret = [];
            foreach($rows as $v) {
                $ret[$v['obj_id']] = [
                    'of_id' => $main['id'],
                    'source_item_id' => $v['obj_id'],
                    'bm_id' => $v['goods_id'],
                    'bn' => $v['bn'],
                    'item_type' => 'sales',
                    'name' => $v['name'],
                    'divide' => $v['divide_order_fee'],
                    'amount' => $v['divide_order_fee'],
                    'quantity' => $v['quantity'],
                    'reship_num' => sprintf('%.0f', $v['quantity'] * $return[$v['obj_id']]),
                    'is_delete' => $v['delete']
                ];
            }
        }else{
            foreach($items as $v) {
                $ret[$v['item_id']] = [
                    'of_id' => $main['id'],
                    'source_item_id' => $v['item_id'],
                    'bm_id' => $v['product_id'],
                    'bn' => $v['bn'],
                    'item_type' => 'basic',
                    'name' => $v['name'],
                    'divide' => $v['divide_order_fee'],
                    'amount' => $v['divide_order_fee'],
                    'quantity' => $v['nums'],
                    'reship_num' => $v['return_num'],
                    'is_delete' => $v['delete']
                ];
            }
        }
        return $ret;
    }
    #人工操作
    public function operateTax($arr) {
        $objFront = app::get('invoice')->model('order_front');
        $oldRow = $objFront->db_dump(['source_id' => $arr['order_id'],'source' => 'b2c']);
        if($oldRow) {
            $upData = [];
            if($arr['is_tax']) {
                $is_status = $arr['is_tax'] == 'true' ? '0' : '2';
                if($is_status != $oldRow['is_status']) {
                    $upData['is_status'] = $is_status;
                }
            };
            if($arr['invoice_kind']) {
                if($arr['invoice_kind'] != $oldRow['mode']) {
                    $upData['mode'] = $arr['invoice_kind'];
                }
            };
            if($arr['source_status'] == 'TRADE_FINISHED') {
                if($oldRow['status'] != 'finish') {
                    $upData['status'] = 'finish';
                }
            } elseif ($arr['source_status'] == 'TRADE_CLOSED') {
                if($oldRow['status'] != 'close') {
                    $upData['status'] = 'close';
                }
            }
            if($arr['title']) {
                if($arr['title'] != $oldRow['title']) {
                    $upData['title'] = $arr['title'];
                }
            };
            if($arr['ship_tax']) {
                if($arr['ship_tax'] != $oldRow['ship_tax']) {
                    $upData['ship_tax'] = $arr['ship_tax'];
                }
            };
            if(empty($upData)) {
                return [false, ['msg'=>'无更新数据']];
            }
            $objFront->update($upData, ['id'=>$oldRow['id']]);
            kernel::single('invoice_order_front')->eventUpdate($oldRow['id'], $upData);
            return [true, ['msg'=>'更新成功']];
        }
        $orderField   = 'order_id,order_bn,ship_name,ship_area,ship_addr,ship_mobile,ship_tel,shop_id,shop_type,is_tax,total_amount as invoice_amount,ship_status';
        $order = app::get('ome')->model('orders')->db_dump(['order_id'=>$arr['order_id']], $orderField . ',source_status');
        if(empty($order)) {
            $order = app::get('archive')->model('orders')->db_dump(['order_id'=>$arr['order_id']], $orderField);
        }
        $params = $order;
        $source_status = $arr['source_status'] ? : $order['source_status'];
        $shopInfo =  app::get('invoice')->model('channel')->get_channel_info($order['shop_id']);
        if ($shopInfo['einvoice_operating_conditions'] == '1' && $order['ship_status'] == '1' && $source_status != 'TRADE_CLOSED') {//发货完成
            $source_status = 'TRADE_FINISHED';
        }
        if($source_status == 'TRADE_FINISHED') {
            $params['status'] = 'finish';
        } elseif ($source_status == 'TRADE_CLOSED') {
            $params['status'] = 'close';
        }
        $oi = app::get('ome')->model('order_invoice')->db_dump(['order_id'=>$arr['order_id']]);
        if($oi) {
            if($oi['invoice_amount'] == 0) {
                unset($oi['invoice_amount']);
            }
            $params = array_merge($params, $oi);
        }
        list($rs, $rsData) = kernel::single('invoice_order')->getInvoiceMoney($order);
        if($rs) {
            $params['invoice_amount'] = $rsData['amount'];
        }
        $params['invoice_kind'] = $params['invoice_kind'] == '1' ? '1' : ($params['invoice_kind'] == '0' ? '2' : '3');
        if ($arr['title']) {
            $params['tax_title'] = $arr['title'];
        }
        if ($arr['ship_tax']) {
            $params['register_no'] = $arr['ship_tax'];
        }
        return kernel::single('invoice_order_front')->insertOrUpdateByOrder($params);
    }
}