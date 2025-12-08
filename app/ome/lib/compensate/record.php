<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_compensate_record {

    /**
     * timeSync
     * @return mixed 返回值
     */
    public function timeSync() {
        $shops = app::get('ome')->model('shop')->getList('shop_id, config', ['node_type'=>'360buy']);
        if(empty($shops)) {
            return false;
        }
        foreach($shops as $shop) {
            $shop['config'] = @unserialize($shop['config']);
            $shop['config'] = $shop['config'] ? $shop['config'] : array();
            if($shop['config']['compensate'] == 'sync') {
                kernel::single('ome_event_trigger_shop_compensate')->syncRecord($shop['shop_id']);
                kernel::single('ome_event_trigger_shop_compensate')->syncIndemnity($shop['shop_id']);
            }
        }
    }

    /**
     * insertAftersale
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function insertAftersale($id){
        return [false, ['msg'=>'不写入售后单']];
        $model = app::get('ome')->model('compensate_record');
        $row = $model->db_dump(['id'=>$id]);
        if($row['check_status'] != '1') {
            return [false, ['msg'=>$row['compensate_bn'].'未审核完成']];
        }
        if($row['in_billcenter'] == 'succ') {
            return [true, ['msg'=>$row['compensate_bn'].'已写入售后单完成']];
        }
        if(empty($row['order_bn'])) {
            $model->update(['in_billcenter'=>'fail', 'in_billcenter_msg'=>'缺少订单'], ['id'=>$id]);
            return [false, ['msg'=>$row['compensate_bn'].'写入售后单失败']];
        }
        $order = app::get('ome')->model('orders')->db_dump(['order_bn'=>$row['order_bn'], 'shop_id'=>$row['shop_id'], 'status|noequal'=>'dead'], 'order_id');
        if(empty($order)) {
            $model->update(['in_billcenter'=>'fail', 'in_billcenter_msg'=>'订单不存在'], ['id'=>$id]);
            return [false, ['msg'=>$row['compensate_bn'].'写入售后单失败']];
        }
        $orderItems = app::get('ome')->model('order_items')->getList('*', ['order_id'=>$order['order_id'], 'delete'=>'false']);
        if(empty($orderItems)) {
            $model->update(['in_billcenter'=>'fail', 'in_billcenter_msg'=>'缺少可用的订单明细'], ['id'=>$id]);
            return [false, ['msg'=>$row['compensate_bn'].'写入售后单失败']];
        }
        $shop = app::get('ome')->model('shop')->db_dump($row['shop_id'],'shop_bn,name');
    
        $bmIds           = array_unique(array_column($orderItems, 'product_id'));
        $materialExtList = app::get('material')->model('basic_material_ext')->getList('bm_id,retail_price', ['bm_id' => $bmIds]);
        $materialExtList = array_column($materialExtList, null, 'bm_id');
        
        $data = [
            'order_bn' => $row['order_bn'],
            'po_bn' => $row['order_bn'],
            'bill_bn' => $row['compensate_bn'],
            'bill_type' => 'COMPENSATE',
            'bill_id' => $row['id'],
            'shop_id' => $row['shop_id'],
            'shop_bn' => $shop['shop_bn'],
            'shop_name' => $shop['name'],
            'aftersale_time' => strtotime($row['at_time']),
            'original_bn' => $row['compensate_bn'],
            'original_id' => $row['id'],
            'settlement_amount' => $row['compensateamount'],
        ];
        $options = array (
            'part_total'  => $data['total_amount'],
            'part_field'  => 'amount',
            'porth_field' => 'divide_order_fee',
        );
        $orderItems = kernel::single('ome_order')->calculate_part_porth($orderItems, $options);
        $items = [];
        foreach($orderItems as $v) {
            $retail_price = $materialExtList[$v['product_id']]['retail_price'] ?? 0;
            $amount       = $retail_price * $v['nums'];
    
            $data['total_amount'] += $amount;
            $data['total_sale_price']  += $v['amount'];
            
            $items[] = [
                'material_bn' => $v['bn'],
                'barcode' => $v['bn'],
                'material_name' => $v['name'],
                'bm_id' => $v['product_id'],
                'nums' => $v['nums'],
                'price' => $retail_price,
                'amount' => $amount,
                'sale_price' => $v['amount'],
            ];
        }
        $data['items'] = $items;
        $baModel = app::get('billcenter')->model('aftersales');
        list($rs, $msg) = $baModel->create_aftersales($data);
        if(!$rs) {
            $model->update(['in_billcenter'=>'fail', 'in_billcenter_msg'=>$msg], ['id'=>$id]);
            return [false, ['msg'=>$row['compensate_bn'].$msg]];
        }
        $model->update(['in_billcenter'=>'succ', 'in_billcenter_msg'=>''], ['id'=>$id]);
        return [true, []];
    }
}