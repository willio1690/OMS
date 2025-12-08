<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2022/6/20 18:16:20
 * @describe: 京东平台拆
 * ============================
 */
class ome_order_platform_split {
    
    /**
     * dealOrderObjects
     * @param mixed $order order
     * @param mixed $split_status split_status
     * @param mixed $split_oid ID
     * @param mixed $oid_num ID
     * @return mixed 返回值
     */

    public function dealOrderObjects($order, &$split_status, $split_oid = '', $oid_num = '') {
        $oOrder = app::get('ome')->model('orders');
        $order_id = $order['order_id'];
        //开启添加发货单事务,锁定当前订单记录
        kernel::database()->beginTransaction();
        //防止订单编辑与生成发货单/平台拆分记录并发导致错误
        $oOrder->update(['last_modified'=>time()], ['order_id'=>$order_id]);
        // 判断是否存在发货单
        $deliveryMdl           = app::get('ome')->model('delivery');
        $deliveryOrderMdl      = app::get('ome')->model('delivery_order');
        $filter = array(
            'delivery_id'  => array(),
            'status|notin' => array('back','cancel','return_back'),
            'parent_id'    => '0',
        );
        foreach ($deliveryOrderMdl->getList('*',array('order_id'=>$order['order_id'])) as $value) {
            $filter['delivery_id'][] = $value['delivery_id'];
        }
        if ($filter['delivery_id']) {
            if($deliveryMdl->getList('delivery_id',$filter)) {
                kernel::database()->rollBack();
                return [false, '已经生成发货单，不能再进行子单拆'];
            }
        }
        $nOrder = app::get('ome')->model('orders')->db_dump(['order_id'=>$order_id], 'is_modify');
        if($nOrder['is_modify'] == 'true') {
            kernel::database()->rollBack();
            return [false, '订单被编辑过，不能再进行子单拆'];
        }
        $split_oid = $split_oid ? : $this->gen_id($order_id);
        $psObj = app::get('ome')->model('order_platformsplit');
        foreach ($order['objects'] as $ok => $object) {
            if($psObj->db_dump(['obj_id'=>$object['obj_id']], 'id')) {
                kernel::database()->rollBack();
                return [false, '子单已拆过，不能再拆:'.$object['bn']];
            }
            if(empty($object['oid'])) {
                kernel::database()->rollBack();
                return [false, '商品为新增或赠品，不能拆子单:'.$object['bn']];
            }
            $quantity = $object['quantity'];
            if($oid_num) {
                if(empty($object['sku_uuid'])) {
                    kernel::database()->rollBack();
                    return [false, '商品缺少sku uuid，不能拆数量:'.$object['bn']];
                }
                $numArr = explode('|', $oid_num);
                foreach($numArr as $nk => $num) {
                    $num = trim($num);
                    if($quantity <= 0) {
                        break;
                    }
                    if($quantity > $num) {
                        $quantity -= $num;
                    } else {
                        $num = $quantity;
                        $quantity = 0;
                    }
                    $inData = [
                        'order_id' => $order_id,
                        'obj_id' => $object['obj_id'],
                        'split_oid' => $split_oid.$nk,
                        'num' => $num,
                        'bn' => $object['bn'],
                        'create_time' => time()
                    ];
                    $psObj->insert($inData);
                }
            } 
            if($quantity > 0) {
                $inData = [
                    'order_id' => $order_id,
                    'obj_id' => $object['obj_id'],
                    'split_oid' => $split_oid,
                    'num' => $quantity,
                    'bn' => $object['bn'],
                    'create_time' => time()
                ];
                $psObj->insert($inData);
            }
            $updateSql = 'update sdb_ome_order_items set split_num=nums where obj_id="'.$object['obj_id'].'"';
            kernel::database()->exec($updateSql);
        }
        $is_splited   = app::get('ome')->model('order_items')->is_splited($order_id);
        $split_status = $is_splited ? 'splited' : 'splitting';
        if($split_status == 'splited') {
            $listOid = $psObj->getList('distinct split_oid', ['order_id'=>$order_id]);
            if(count($listOid) < 2) {
                kernel::database()->rollBack();
                return [false, '必须拆分成2个子单以上'];
            }
        }
        $oOrder->update(['process_status'=>$split_status], ['order_id'=>$order_id]);
        kernel::database()->commit();
        if($split_status == 'splited') kernel::single('ome_event_trigger_shop_order')->split_oid_sync($order_id);
        return [true];
    }

    /**
     * gen_id
     * @param mixed $order_id ID
     * @return mixed 返回值
     */
    public function gen_id($order_id) {
        $psObj = app::get('ome')->model('order_platformsplit');
        do {
            $id = time().rand(0,9).rand(0,9).rand(0,9);
            if(!$psObj->db_dump(['order_id'=>$order_id, 'split_oid'=>$id], 'id')) {
                break;
            }
        } while(true);
        return $id;
    }

}