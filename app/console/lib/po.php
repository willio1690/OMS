<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_po
{
    /**
     * 采购单审核
     *
     * @return void
     * @author
     **/
    public function do_check($po_id)
    {
        $err_msg='';
        if (!$po_id) {
            return array(false, '采购单ID不能为空');
        }

        $mdl_po = app::get('purchase')->model('po');
        $aRow   = $mdl_po->dump($po_id, '*', array('po_items' => array('product_id,num')));

        if (!$aRow) {
            return array(false, '采购单不存在');
        }

        if ($aRow['check_status'] == '2') {
            return array(false, '采购单已审核');
        }

        // 生成在途
        $storeManageLib = kernel::single('ome_store_manage');
        $storeManageLib->loadBranch(array('branch_id' => $aRow['branch_id']));
        $params                    = array();
        $params['node_type']       = 'changeArriveStore';
        $params['params']          = array(
            'obj_id' => $aRow['po_id'],
            'branch_id' => $aRow['branch_id'],
            'obj_type' => 'purchase',
            'operator' => '+'
        );
        $params['params']['items'] = $aRow['po_items'];
        $storeManageLib->processBranchStore($params, $err_msg);

        $payObj = app::get('purchase')->model('purchase_payments');
        $pay_bn = $payObj->gen_id();

        $row                = array();
        $row['payment_bn']  = $pay_bn;
        $row['po_id']       = $aRow['po_id'];
        $row['po_type']     = $aRow['po_type'];
        $row['add_time']    = time();
        $row['supplier_id'] = $aRow['supplier_id'];

        $oper = kernel::single('ome_func')->getDesktopUser();

        $row['operator'] = $oper['op_name'];

        if ($aRow['po_type'] == 'cash') {
            //现购,生成付款单
            $row['payable']       = $aRow['product_cost'] + $aRow['delivery_cost'];
            $row['deposit']       = 0;
            $row['product_cost']  = $aRow['product_cost'];
            $row['delivery_cost'] = $aRow['delivery_cost'];
            $payObj->save($row);
        } elseif ($aRow['po_type'] == 'credit' && $aRow['deposit'] > 0) {

            //赊购,预付款不为0时生成付款单
            $row['payable']       = $aRow['deposit'];
            $row['deposit']       = $aRow['deposit'];
            $row['product_cost']  = 0;
            $row['delivery_cost'] = 0;
            $payObj->save($row);
        }

        $rs = app::get('purchase')->model('po')->update(array(
            'check_status'   => 2,
            'eo_status'      => 1,
            'check_time'     => time(),
            'check_operator' => $oper['op_name'],
        ), array('po_id' => $po_id, 'check_status' => 1));
        if(is_bool($rs)) {
            return array(false, '采购单状态已改变，不能再审核');
        }
        kernel::single('console_event_trigger_purchase')->create(array('po_id' => $po_id), false);
        $log_msg = '审核完成';
        $opObj   = app::get('ome')->model('operation_log');
        $opObj->write_log('purchase_modify@purchase', $po_id, $log_msg);
        return array(true, '审核完成');
    }
}
