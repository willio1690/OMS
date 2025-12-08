<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Created by PhpStorm.
 * User: yaokangming
 * Date: 2019/4/22
 * Time: 16:10
 */
class purchase_branch
{
    /**
     * feedbackDelivery
     * @param mixed $owId ID
     * @return mixed 返回值
     */

    public function feedbackDelivery($owId) {

        $modelWait = app::get('purchase')->model('order_wait');
        $orderWait = $modelWait->db_dump($owId);
        if($orderWait['status'] == 'ROLLBACK') {
            return array(true, '状态ROLLBACK不需要寻仓');
        }

        if (!$orderWait['available_warehouses']) return array(false, '唯品会未指定仓库');

        list($rs,$msg) = $this->waitToOrder($orderWait);
        if(!$rs) return array(false, $msg);
        
        if (!$orderWait['branch_id']) {
            $gOrder = $msg;
            $storePlugObj = new omeauto_auto_plugin_store();
            $itemObject   = new omeauto_auto_group_item($gOrder);
            $confirmRoles = array();
            $branchIds = kernel::single('ome_branch_type')
                ->getBranchIdByStoreCode(explode(',', $orderWait['available_warehouses']), 'vopjitx');

            $itemObject->setBranchId($branchIds);
         
            $storePlugObj->process($itemObject, $confirmRoles);
            $branchId = $itemObject->getBranchId();

            if (!$branchId || !$itemObject->validStatus()) {
                return array(false, '寻仓失败：商品库存不足');
            }

            list($rs,$msg) = kernel::single('purchase_branch_freeze')->add($owId, $branchId);
            if(!$rs) return array($rs,$msg);
            
            $upData = array(
                'branch_id' => $branchId,
                'warehouse' => array_search($branchId, $branchIds)
            );
            $modelWait->update($upData, array('ow_id'=>$owId));
            $orderWait = array_merge($orderWait, $upData);
        }

        $rsp = kernel::single('erpapi_router_request')
            ->set('shop', $orderWait['shop_id'])
            ->branch_feedbackDelivery($orderWait);

        if($rsp['rsp'] != 'succ') {
            return array(false, '寻仓请求失败：'.$rsp['msg']);
        }

        $rs = $modelWait->update(array('status'=>'CONFIRMING'), array('ow_id'=>$owId, 'status'=>'NEW'));

        $operateLog = app::get('ome')->model('operation_log');
        $operateLog->write_log('purchase_order_wait@purchase',$owId,'寻仓请求成功,更新为确认中');

        return array(true, '寻仓请求成功');
    }

    /**
     * waitToOrder
     * @param mixed $orderWait orderWait
     * @return mixed 返回值
     */
    public function waitToOrder($orderWait) {
        $order = array();
        $order[$orderWait['ow_id']] = array(
            'order_bn' => $orderWait['order_bn'],
            'shop_id' => $orderWait['shop_id'],
            'shop_type' => $orderWait['shop_type'],
            'ship_addr' => $orderWait['buyer_address']
        );
        $modelWaitItems = app::get('purchase')->model('order_wait_items');
        $orderWaitItems = $modelWaitItems->getList('*,quantity as nums', array('ow_id'=>$orderWait['ow_id']));

        foreach ($orderWaitItems as $item) {
            if(empty($item['product_id'])) {
                $bn = kernel::single('material_codebase')->getBnBybarcode($item['barcode']);
                $product = app::get('material')->model('basic_material')->db_dump(array('material_bn'=>$bn),'bm_id');
                if(empty($product)) {
                    return array(false, '明细缺少商品');
                }
                $tmp = array();
                $tmp['product_id'] = $product['bm_id'];
                $tmp['bn']         = $bn;
                $modelWaitItems->update($tmp, array('owi_id'=>$item['owi_id']));
                $item = array_merge($item, $tmp);
            }

            $order[$orderWait['ow_id']]['objects'][$item['owi_id']] = $item;
            $order[$orderWait['ow_id']]['items'][$item['owi_id']] = $item;
        }
        return array(true, $order);
    }
}