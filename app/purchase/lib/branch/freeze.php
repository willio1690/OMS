<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Created by PhpStorm.
 * User: yaokangming
 * Date: 2019/4/23
 * Time: 14:41
 */
class purchase_branch_freeze
{
    /**
     * 添加
     * @param mixed $owId ID
     * @param mixed $branchId ID
     * @return mixed 返回值
     */

    public function add($owId, $branchId = 0) {
        $modelWait = app::get('purchase')->model('order_wait');
        $orderWait = $modelWait->db_dump(array('ow_id'=>$owId), 'order_bn,branch_id');
        $orderWait['branch_id'] = $orderWait['branch_id'] ? $orderWait['branch_id'] : $branchId;
        if(empty($orderWait['branch_id'])) {
            return array(false, '没有出库仓');
        }
        $orderWaitItems = app::get('purchase')->model('order_wait_items')->getList(
            'product_id,quantity,bn', array('ow_id'=>$owId)
        );
        if(empty($orderWaitItems)) {
            return array(false, '没有商品明细');
        }

        // 先不去冻结，后期需要重新梳理冻结逻辑
        /*
        $freezeMdl  = app::get('material')->model('basic_material_stock_artificial_freeze');
        $productMdl = app::get('ome')->model('products');
        $bpMdl      = app::get('ome')->model('branch_product');
        $logMdl     = app::get('ome')->model('operation_log');

        $oper = kernel::single('ome_func')->getDesktopUser();

        $storeManageLib = kernel::single('ome_store_manage');
        $params['node_type'] = "artificialFreeze";
        $storeManageLib->loadBranch(array('branch_id'=>$orderWait['branch_id']));
        foreach($orderWaitItems as $value){
            $freeze = array(
                'branch_id'     => $orderWait['branch_id'],
                'product_id'    => $value['product_id'],
                'bm_id'         => $value['product_id'],
                'freeze_num'    => $value['quantity'],
                'freeze_reason' => sprintf('[%s]JITX寻仓预占', $orderWait['order_bn']),
                'freeze_time'   => time(),
                'op_id'         => $oper['op_id'],
                'original_bn'   => $orderWait['order_bn'],
                'original_type' => 'orderwaitout',
                'bn'            => $value['bn'],
            );

            $freezeMdl->insert($freeze);
            
            // $logMdl->write_log('import_artificial_freeze@ome',$freez['psaf_id'],$freeze);
            //$freez['psaf_id']
            //库存管控
            $params['params'][] = array_merge(array("obj_id"=>$freeze['psaf_id']),$freeze);
        }
        $storeManageLib->processBranchStore($params,$err_msg);
        $logMdl->write_log('purchase_order_wait@purchase',$owId,'JITX待寻仓订单仓库冻结');
        */

        return array (true, '成功');
    }

    /**
     * 删除
     * @param mixed $orderWaitId ID
     * @return mixed 返回值
     */
    public function delete($orderWaitId) {
        $modelOrderWait = app::get('purchase')->model('order_wait');
        $orderWait = $modelOrderWait->db_dump(array('ow_id' => $orderWaitId), 'ow_id,order_bn');
        if (empty($orderWait)) {
            return array(false, '没有待寻仓订单');
        }
        return $this->deleteOrderWaitFreeze($orderWait);
    }

    /**
     * 删除OrderWaitFreeze
     * @param mixed $orderWait orderWait
     * @return mixed 返回值
     */
    public function deleteOrderWaitFreeze($orderWait) {
        $orderWaitId = $orderWait['ow_id'];

        $freezeMdl  = app::get('material')->model('basic_material_stock_artificial_freeze');
       
        $freeze_items = $freezeMdl->getList('*', array (
            'original_bn'   => $orderWait['order_bn'],
            'original_type' => 'orderwaitout',
            'status'        => '1',
        ));

        if (!$freeze_items) return array(false, '没有冻结');

        $oper = kernel::single('ome_func')->getDesktopUser();
        $storeManageLib = kernel::single('ome_store_manage');
        $params['node_type'] = "artificialUnfreeze";
        $storeManageLib->loadBranch(array('branch_id'=>$orderWait['branch_id']));

        $trans = kernel::database()->beginTransaction();
        foreach ($freeze_items as $item) {
            $affect_rows = $freezeMdl->update(
                    array ('status' => '2', 'op_id' => $oper['op_id'], 'update_modified' => time()),
                    array ('bmsaf_id' => $item['bmsaf_id'], 'status' => '1')
                );

            if ($affect_rows !== 1) {
                kernel::database()->rollBack();
                return array (false, '释放预占失败');
            }

            $params['params'][] = $item;
        }
        
        $res = $storeManageLib->processBranchStore($params,$err_msg);
        if ($res) {
            kernel::database()->commit($trans);
        } else {
            kernel::database()->rollBack();
        }
        return array (true, '释放预占成功');
    }

    /**
     * 删除FromOrder
     * @param mixed $orderBn orderBn
     * @param mixed $shopId ID
     * @return mixed 返回值
     */
    public function deleteFromOrder($orderBn, $shopId) {
        $modelOrderWait = app::get('purchase')->model('order_wait');
        $orderWait = $modelOrderWait->db_dump(array('order_bn'=>$orderBn, 'shop_id'=>$shopId), 'ow_id,order_bn');
        if (empty($orderWait)) {
            return array(false, '没有待寻仓订单');
        }
        return $this->deleteorderWaitFreeze($orderWait);
    }

    /**
     * 获取BranchId
     * @param mixed $orderBn orderBn
     * @param mixed $shopId ID
     * @return mixed 返回结果
     */
    public function getBranchId($orderBn, $shopId) {
        $modelOrderWait = app::get('purchase')->model('order_wait');
        $orderWait = $modelOrderWait->db_dump(array('order_bn'=>$orderBn, 'shop_id'=>$shopId), 'ow_id,branch_id');
        return $orderWait ? $orderWait['branch_id'] : 0;
    }
}