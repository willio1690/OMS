<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2022/7/5 14:20:33
 * @describe
 */
class erpapi_shop_matrix_tmall_request_maochao_aftersale extends erpapi_shop_request_aftersale {

    /**
     * 卖家确认收货
     * @param $data
     */

    public function returnGoodsConfirm($sdf)
    {
        $title = '售后确认收货['.$sdf['return_bn'].']';
        $specialObj = app::get('ome')->model('return_product_tmall');
        $ras = $specialObj->db_dump(array('return_id'=>$sdf['return_id']), 'extend_field');
        $extend_field = $ras ? json_decode($ras['extend_field'], 1) : array();
        $reship = app::get('ome')->model('reship')->db_dump(['return_id'=>$sdf['return_id']], 'reship_id,t_end,branch_id,order_id');
        $branch = app::get('ome')->model('branch')->db_dump(['branch_id'=>$reship['branch_id'], 'check_permission'=> 'false'], 
            'branch_id,branch_bn');
        $order_objects = app::get('ome')->model('order_objects')->getList(
            'obj_id,order_id,oid,shop_goods_id,quantity', ['order_id'=>$reship['order_id'], 'delete'=>'false']);
        $order_objects = array_column($order_objects, null, 'obj_id');
        $item_obj_id = app::get('ome')->model('order_items')->getList('item_id, obj_id, nums', ['order_id'=>$reship['order_id']]);
        $item_obj_id = array_column($item_obj_id, null, 'item_id');
        $reship_items = app::get('ome')->model('reship_items')->getList(
            'product_id, num, defective_num, normal_num, order_item_id', ['reship_id'=>$reship['reship_id'], 'return_type'=>'return']);
        $order_items = [];
        foreach ($reship_items as $key => $value) {
            $obj_id = $item_obj_id[$value['order_item_id']]['obj_id'];
            if($order_items[$obj_id]) {
                continue;
            }
            $oo = $order_objects[$obj_id];
            $radio = $oo['quantity']/$item_obj_id[$value['order_item_id']]['nums'];
            $actual_received_quantity = bcmul($value['num'], $radio);
            $normal_num = $value['normal_num'] ? bcmul($value['normal_num'], $radio) : ($value['defective_num'] ? 0 : $actual_received_quantity);
            $defective_num = bcmul($value['defective_num'], $radio);
            $order_items[$obj_id] = [
                'sub_order_code' => $extend_field['items'][$oo['shop_goods_id']],
                'sc_item_id' => $oo['shop_goods_id'],
                'actual_received_quantity' => $actual_received_quantity,
                'actual_lack_quantity' => bcsub($oo['quantity'], $actual_received_quantity),
                'instorage_details' => []
            ];
            if($normal_num) {
                $order_items[$obj_id]['instorage_details'][] = [
                    'received_quantity' => $normal_num,
                    'storage_type' => 1
                ];
            }
            if($defective_num) {
                $order_items[$obj_id]['instorage_details'][] = [
                    'received_quantity' => $defective_num,
                    'storage_type' => 101
                ];
            }
        }
        $data = array(
            'supplier_id' => $extend_field['supplierId'],
            'biz_order_code' => $sdf['return_bn'],
            'instorage_time' => date('Y-m-d H:i:s', $reship['t_end']),
            'store_code' => $branch['branch_bn'],
            'receiver_info' => json_encode($extend_field['receiver_info']),
            'sender_info' => json_encode($extend_field['sender_info']),
            'order_items' => json_encode(array_values($order_items)),
        );
        $this->__caller->call(SHOP_SUPPLIER_RETURN_GOOD_CONFIRM, $data, array(), $title, 10, $sdf['return_bn']);
    }
}