<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class vop_pick_stockout_bills
{
    /**
     * do_stockout
     * @param mixed $stockout_info stockout_info
     * @param mixed $box_list box_list
     * @return mixed 返回值
     */
    public function do_stockout(&$stockout_info, $box_list)
    {
        $stockout_id = $stockout_info['stockout_id'];

        if (!$stockout_id) return array(false, '出库单不能为空');

        $pick_bills = array(); $stockout_product_list = array();
        // 更新发货明细
        foreach ($box_list as $value) {
            if (!$value['stockout_item_id']) return array(false, sprintf('货号[%s]出库明细ID不能为空', $value['bn']));
            if (!$value['box_no'])           return array(false, sprintf('货号[%s]箱号不能为空', $value['bn']));
            if (!$value['box_num'])          return array(false, sprintf('货号[%s]数量不能为空', $value['bn']));
            if (!$value['bill_id'])          return array(false, sprintf('货号[%s]拣货单ID不能为空', $value['bn']));
            if (!$value['po_id'])            return array(false, sprintf('货号[%s]采购单ID不能为空', $value['bn']));
            if (!$value['bn'])               return array(false, '货号不能为空');

            // 更新出库明细
            $affect_rows = kernel::database()->exec(sprintf('UPDATE sdb_purchase_pick_stockout_bill_items SET actual_num=actual_num+%s WHERE stockout_item_id=%s AND num>=actual_num+%s',$value['box_num'],$value['stockout_item_id'],$value['box_num']));

            if (is_bool($affect_rows)) {
                return array(false, sprintf('货号[%s]出库数量超出可出库数量', $value['bn']));
            }

            // 拣货单发货数量
            $pick_bills[$value['bill_id']]['branch_send_num'] += $value['box_num'];

            // 货号出库数
            $stockout_product_list[strtoupper($value['bn'])] += $value['box_num'];
        }

        $item_num = $actual_num = 0;

        // 更新出库单状态
        $stockoutItemModel = app::get('purchase')->model('pick_stockout_bill_items');
        $stockout_items = $stockoutItemModel->getList('*',array('stockout_id'=>$stockout_id, 'is_del'=>'false'));
        foreach ($stockout_items as $value) {
            $item_num   += $value['num'];
            $actual_num += $value['actual_num'];
        }

        $o_status = 2; $status   = 1;
        if ($item_num == $actual_num) {
            $o_status = 3; $status = 3;
        }

        $data = array(
            'last_modified'  => time(),
            'o_status'       => $o_status,
            'status'         => $status,
            'branch_out_num' => $actual_num,
            'ship_time' => time(),
            'complete_time' => time(),
        );
        
        $stockoutMdl = app::get('purchase')->model('pick_stockout_bills');
        $affect_rows = $stockoutMdl->update($data,array('stockout_id'=>$stockout_id, 'status'=>1, 'o_status'=>array(1, 2)));
        if (is_bool($affect_rows)) {
            return array(false, '状态异常，出库失败');
        }

        // 更新拣货单
        foreach ($pick_bills as $bill_id => $value) {
            kernel::database()->exec(sprintf('UPDATE sdb_purchase_pick_bills SET branch_send_num=branch_send_num+%s WHERE bill_id=%s AND pick_num>=branch_send_num+%s', $value['branch_send_num'],$bill_id,$value['branch_send_num']));

            $affect_rows = kernel::database()->affect_row();


            if (is_bool($affect_rows)) return array(false, sprintf('更新拣货单[%s]出库数量失败', $bill_id));
        }

        $logObj = app::get('ome')->model('operation_log');

        $pickBillModel = app::get('purchase')->model('pick_bills');
        foreach ($pickBillModel->getList('bill_id, pick_num, branch_send_num, delivery_status, pick_no', array('bill_id'=>array_keys($pick_bills))) as $value) {
            $affect_rows = $pickBillModel->update(array('delivery_status'=>$value['pick_num']==$value['branch_send_num']?2:3),array('bill_id'=>$value['bill_id'],'delivery_status'=>array(1,3)));

            if (is_bool($affect_rows)) return array(false, '更新拣货单出库数量失败');

            $logObj->write_log('check_vopick@ome', $value['bill_id'], $value['pick_num']==$value['branch_send_num']?'全部发货':'部分发货');

            // 删除拣货单明细的详单
            if ($status == '3') {
                $inventoryMdl = app::get('purchase')->model('pick_bill_item_inventory');
                $inventoryMdl->delete(['pick_no'=>$value['pick_no']]);
            }
        }


        // 插入箱号
        $box_data = array();
        foreach ($box_list as $value) {
            $box_data[] = array(
                'stockout_id'      => $stockout_id,
                'stockout_item_id' => $value['stockout_item_id'],
                'po_id'            => $value['po_id'],
                'bill_id'          => $value['bill_id'],
                'box_no'           => $value['box_no'] ?? '',
                'num'              => $value['box_num'],
            );
        }

        $sql = ome_func::get_insert_sql(app::get('purchase')->model('pick_stockout_bill_item_boxs'),$box_data);
        if (!kernel::database()->exec($sql)) {
            return array(false, '插入箱号失败');
        }

        // 更新日志
        $logObj->write_log('update_stockout_bills@ome',$stockout_id,$o_status==3?'全部出库':'部分出库');

        // 发货状态更新结果返回 
        $stockout_info['o_status'] = $o_status;

        return array(true, '出库成功');
    }
}
