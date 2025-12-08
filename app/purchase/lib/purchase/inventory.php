<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 唯品会JIT拣货单明细的详单Lib类
 *
 * @author wangbiao@shopex.cn
 * @version 0.1
 */
class purchase_purchase_inventory
{

    /**
     * __construct
     * @return mixed 返回值
     */

    public function __construct()
    {
        $this->_stockItemObj = app::get('purchase')->model('pick_stockout_bill_items');
        $this->_inventoryObj = app::get('purchase')->model('pick_bill_item_inventory');
        $this->_pickStockObj = app::get('purchase')->model('pick_stockout');
    }

    /**
     * 处理
     * @param mixed $stockout_id ID
     * @param mixed $error_msg error_msg
     * @return mixed 返回值
     */
    public function process($stockout_id = '', &$error_msg = [])
    {
        if (!$stockout_id) {
            return false;
        }
        $pick = $this->getPickFromStockout($stockout_id);
        if (!$pick) {
            return false;
        }
        $pickNoList = array_column($pick, 'pick_no');

        // 获取出库单
        $stockoutItems = $this->_stockItemObj->getList('*', ['stockout_id' => $stockout_id]);

        /*
        // ** 因为sdb_purchase_pick_bill_item_inventory实际接口返回的数据pick_no是空，所以不再去更新此表
        // 查出拣货单对应的详单
        $inventory        = $this->_inventoryObj->getList('*', ['pick_no|in' => $pickNoList]);
        $inventoryBarcode = [];
        foreach ($inventory as $k => $v) {
            if (!$inventoryBarcode[$v['barcode']]) {
                $inventoryBarcode[$v['barcode']] = [];
            }
            $inventoryBarcode[$v['barcode']][$v['bill_inventory_id']] = $v;
        }
        unset($inventory);
        */

        // 根据出库单更新详单的已处理数量
        $batchList = [];
        foreach ($stockoutItems as $k => $v) {

            $freezeData = [];
            $freezeData['bm_id']    = $v['product_id'];
            $freezeData['sm_id']    = 0; // 唯品会拣货单是通过barcode获取的bm_id,没有销售物料
            $freezeData['obj_type'] = material_basic_material_stock_freeze::__ORDER;
            $freezeData['bill_type']= material_basic_material_stock_freeze::__VOPICKBILLS;
            $freezeData['obj_id']   = $v['bill_id'];
            $freezeData['branch_id']= 0;
            $freezeData['bmsq_id']  = material_basic_material_stock_freeze::__SHARE_STORE;
            $freezeData['num']      = $v['num'];
            $freezeData['bm_bn']    = $v['bn'];

            $batchList[] = $freezeData;

            /*
            $stockoutNum = $v['num'];
            if ($inventoryBarcode[$v['barcode']]) {
                foreach ($inventoryBarcode[$v['barcode']] as $bill_inventory_id => $detail) {
                    if ($detail['amount'] - $detail['num'] <= 0) {
                        continue;
                    }
                    if ($stockoutNum > ($detail['amount'] - $detail['num'])) {
                        $detail['num'] = $detail['amount'];
                        $stockoutNum -= ($detail['amount'] - $detail['num']);
                    } else {
                        $detail['num'] += $stockoutNum;
                        $stockoutNum -= $stockoutNum;
                    }
                    $this->_inventoryObj->update(['num' => $detail['num']], ['bill_inventory_id' => $bill_inventory_id, 'filter_sql' => 'amount>='.$detail['num']]);

                    $freezeData = [];
                    $freezeData['bm_id']    = $v['product_id'];
                    $freezeData['sm_id']    = 0; // 唯品会拣货单是通过barcode获取的bm_id,没有销售物料
                    $freezeData['obj_type'] = material_basic_material_stock_freeze::__ORDER;
                    $freezeData['bill_type']= material_basic_material_stock_freeze::__VOPICKBILLS;
                    $freezeData['obj_id']   = $v['bill_id'];
                    $freezeData['branch_id']= 0;
                    $freezeData['bmsq_id']  = material_basic_material_stock_freeze::__SHARE_STORE;
                    $freezeData['num']      = $detail['num'];
                    $freezeData['bm_bn']    = $v['bn'];

                    $batchList[] = $freezeData;

                    if (!$stockoutNum) {
                        break;
                    }
                }
            } else {
                $error_msg[] = $v['barcode'].' is not in inventory';
            }
            */
        }
        $err = '';
        $basicMStockFreezeLib = kernel::single('material_basic_material_stock_freeze');
        $rs = $basicMStockFreezeLib->unfreezeBatch($batchList, __CLASS__.'::'.__FUNCTION__, $err);
        if (!$rs) {
            $error_msg[] = '拣货单预占库存释放失败：' . $err;
            return false;
        }
        return true;
    }

    /**
     * 获取PickFromStockout
     * @param mixed $stockout_id ID
     * @return mixed 返回结果
     */
    public function getPickFromStockout($stockout_id)
    {
        $sql  = "SELECT b.bill_id, b.pick_no, b.po_id FROM sdb_purchase_pick_bills as b LEFT JOIN sdb_purchase_pick_stockout as s ON b.bill_id=s.bill_id WHERE s.stockout_id = '" . $stockout_id . "'";
        $list = kernel::database()->select($sql);
        return $list;
    }

}
