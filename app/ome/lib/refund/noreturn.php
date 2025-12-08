<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_refund_noreturn
{
    /**
     * 退款未退货
     * @param $items
     * @param $applyIds
     * @param array $err_msg
     * @return bool
     * @date 2024-07-08 10:34 上午
     */
    public function checkRefundNoreturn($items, $applyIds, &$err_msg = [])
    {
        if (!$applyIds) {
            $err_msg[] = sprintf('缺少参数apply_id');
            return true;
        }

        if (!$items) {
            $err_msg[] = sprintf('缺少参数items');
            return true;
        }
        
        $refundApplyList = app::get('ome')->model('refund_apply')->getList('apply_id,refund_apply_bn,status,order_id,shop_id', ['apply_id' => $applyIds]);
        if (!$refundApplyList) {
            $err_msg[] = sprintf('未检测到退款申请单据apply_id:%s', implode(',', $applyIds));
            return true;
        }
        $shop_id = $refundApplyList[0]['shop_id'];
        $refundApplyList = array_column($refundApplyList, null, 'order_id');
        
        $shopInfo = app::get('ome')->model('shop')->db_dump(['shop_id'=>$shop_id],'shop_bn,shop_id,delivery_mode,shop_type');
        if($shopInfo && $shopInfo['delivery_mode'] == 'jingxiao'){
            $err_msg[] = sprintf('经销发货店铺订单退款不进退款未退货报表shop_bn:%s',$shopInfo['shop_bn']);
            return true;
        }
        
        foreach ((array)$items as $k => $item) {
            $order_item_id = $item['order_item_id'];
            $product_id    = $item['product_id'];
            
            if (!$order_item_id || !$product_id) {
                $err_msg[] = sprintf('缺少明细参数item_id:%s,product_id:%s', $order_item_id, $product_id);
                continue;
            }
            
            $orderMdl       = app::get('ome')->model('orders');
            $orderItemMdl   = app::get('ome')->model('order_items');
            $orderObjectMdl = app::get('ome')->model('order_objects');
            $noreturnMdl    = app::get('ome')->model('refund_noreturn');
            
            //查询订单明细
            $itemInfo = $orderItemMdl->db_dump(['item_id' => $order_item_id, 'product_id' => $product_id], 'item_id,order_id,obj_id,product_id,bn,nums,split_num,sendnum,return_num');
            $order_id = $itemInfo['order_id'];
            if (!isset($refundApplyList[$order_id])) {
                $err_msg[] = sprintf('订单未找到售后退款单据order_id:%s,product_id:%s', $order_id, $product_id);
                continue;
            }
            //查询订单
            $orderInfo = $orderMdl->db_dump(['order_id' => $order_id], 'order_id,order_bn,ship_status');
            
            //查询oid
            $objectInfo = $orderObjectMdl->db_dump(['obj_id' => $itemInfo['obj_id']], 'obj_id,oid,ship_status');
            //未发货拦截    撤销发货单 改状态
            if ($itemInfo['split_num'] == 0) {
                $err_msg[] = sprintf('未发货item_id:%s', $order_item_id);
                continue;
            }
            
            //检测是否已有退货单及退货状态
            $reship = kernel::database()->selectrow("SELECT r.status,r.is_check,r.reship_bn,r.reship_id FROM sdb_ome_reship AS r LEFT JOIN sdb_ome_reship_items AS ri ON r.reship_id = ri.reship_id
                WHERE r.order_id = '" . $order_id . "' AND ri.product_id ='" . $product_id . "'");
            
            $data     = $this->refundNoreturnToData($refundApplyList[$order_id], $orderInfo, $itemInfo, $objectInfo, $reship);
            $noReturn = $noreturnMdl->db_dump(['order_id' => $data['order_id'], 'order_item_id' => $data['order_item_id']], 'id');
            if ($noReturn) {
                $re = $noreturnMdl->update($data, ['id' => $noReturn['id']]);
            } else {
                $re = $noreturnMdl->insert($data);
            }
            if (!$re) {
                $err_msg[] = sprintf('保存退款未退货失败order_id:%s,item_id:%s', $data['order_id'], $data['order_item_id']);
            }
        }
        
        return true;
    }
    
    /**
     * 组装退货未退款数据
     * @param $refundApply
     * @param $order
     * @param $item
     * @param $object
     * @param $reship
     * @return array
     * @date 2024-07-02 10:28 上午
     */
    public function refundNoreturnToData($refundApply, $order, $item, $object, $reship)
    {
        $salesMaterialMdl      = app::get('material')->model('sales_material');
        $salesBasicMaterialMdl = app::get('material')->model('sales_basic_material');
        $smIds                 = $salesBasicMaterialMdl->db_dump(['bm_id' => $item['product_id']], 'sm_id');
        $salesMaterial         = $salesMaterialMdl->db_dump(['sm_id' => $smIds], 'sm_id,sales_material_bn');
        
        $data = array(
            'order_id'          => $order['order_id'],
            'order_bn'          => $order['order_bn'],
            'sales_material_bn' => $salesMaterial['sales_material_bn'],
            'basic_material_bn' => $item['bn'],
            'order_item_id'     => $item['item_id'],
            'order_obj_id'      => $object['obj_id'],
            'oid'               => $object['oid'],
            'order_status'      => $order['ship_status'],
            'oid_status'        => $object['ship_status'],
            'apply_id'          => $refundApply['apply_id'],
            'refund_apply_bn'   => $refundApply['refund_apply_bn'],
            'refund_status'     => $refundApply['status'],//退款状态
        );
        if ($reship) {
            $data['return_id']     = $reship['reship_id'];
            $data['return_bn']     = $reship['reship_bn'];
            $data['return_status'] = $reship['is_check'] == '7' ? 2 : 1;
        }
        
        return $data;
    }
    
    /**
     * 发货时检测是否有退款申请单
     * 如果有则说明可能是撤销发货单失败单据
     * @param $delivery_id
     * @return array
     * @date 2024-07-08 10:46 上午
     */
    public function deliveryRefundNoreturn($delivery_id)
    {
        $deliveryItemsDelMdl = app::get('ome')->model('delivery_items_detail');
        $itemList            = $deliveryItemsDelMdl->getList('item_detail_id,order_id,order_item_id,order_obj_id,product_id,bn,oid', ['delivery_id' => $delivery_id]);
        $deliveryOrders      = app::get('ome')->model('delivery_order')->getList('order_id,delivery_id', ['delivery_id' => $delivery_id]);
        $orderIds            = array_column($deliveryOrders, 'order_id');
        
        $refundApplyList = app::get('ome')->model('refund_apply')->getList('apply_id,refund_apply_bn,status', ['order_id' => $orderIds]);
        $apply_ids       = $refundApplyList ? array_column($refundApplyList, 'apply_id') : [];
        $res = kernel::single('ome_refund_noreturn')->checkRefundNoreturn($itemList, $apply_ids, $errMsg);
        return [true, '成功'];
    }
    
    /**
     * 质检后改退款未退货单据状态
     * @param $reshipInfo
     * @return bool
     * @date 2024-07-03 2:32 下午
     */
    public function reshipRefundNoreturn($order_id, $reship_id)
    {
        $reshipItemsMdl = app::get('ome')->model('reship_items');
        $itemList       = $reshipItemsMdl->getList('item_id,reship_id,order_item_id,bn,product_id', ['reship_id' => $reship_id]);
        $refundApplyList    = app::get('ome')->model('refund_apply')->getList( 'apply_id,refund_apply_bn,status',['order_id' => $order_id]);
        $apply_ids       = $refundApplyList ? array_column($refundApplyList, 'apply_id') : [];
        $res = kernel::single('ome_refund_noreturn')->checkRefundNoreturn($itemList, $apply_ids, $errMsg);
        return [true, '成功'];
    }
    
    /**
     * 定期清理
     * @return bool
     * @date 2024-07-19 2:16 下午
     */
    public function clean()
    {
        // 计算六个月前的日期
        $sixMonthsAgo = date('Y-m-d H:i:s', strtotime('-6 months'));
        $db           = kernel::database();
        $sql          = "SELECT id FROM sdb_ome_refund_noreturn WHERE return_status = '2' AND at_time < '" . $sixMonthsAgo . "'";
        $noreturnList = $db->exec($sql);
        if ($noreturnList) {
            $del_sql = "DELETE FROM `sdb_ome_refund_noreturn` WHERE return_status = '2' AND at_time < '" . $sixMonthsAgo . "'";
            $db->exec($del_sql);
        }
        
        $currentDate     = date('Y-m-d');// 获取当前日期
        $firstDayOfMonth = date('Y-m-01'); // 获取当前月份的第一天
        // 检查当前日期是否为每月的第一天
        if ($currentDate === $firstDayOfMonth) {
            $del_sql = 'OPTIMIZE TABLE `sdb_ome_refund_noreturn`';
            $db->exec($del_sql);
        }
        return true;
    }
}