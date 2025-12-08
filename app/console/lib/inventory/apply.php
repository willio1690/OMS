<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_inventory_apply {

    /**
     * confirm
     * @param mixed $apply_id ID
     * @param mixed $diff_confirm diff_confirm
     * @return mixed 返回值
     */
    public function confirm($apply_id, $diff_confirm = false) {
        $objInAp = app::get('console')->model('inventory_apply');
        $main = $objInAp->db_dump(['inventory_apply_id'=>$apply_id], '*');
        if($main['negative_branch_id']) {
            list($rs, $rsData) = $this->confirmZP($main);
            if(!$rs) {
                return [false, ['msg'=>'确认失败'.$rsData['msg']]];
            }
            if($diff_confirm && $rsData['diff_id']) {
                kernel::single('console_difference')->confirm($rsData['diff_id']);
            }
        }
        if($main['negative_cc_branch_id']) {
            list($rs, $rsData) = $this->confirmCC($main);
            if(!$rs) {
                return [false, ['msg'=>'确认失败'.$rsData['msg']]];
            }
            if($diff_confirm && $rsData['diff_id']) {
                kernel::single('console_difference')->confirm($rsData['diff_id']);
            }
        }
        if(!app::get('console')->model('inventory_apply_items')->db_dump(['inventory_apply_id'=>$main['inventory_apply_id'], 'is_confirm'=>'0'], 'item_id')) {
            $objInAp->update(['status'=>'confirmed','process_date'=>time()], ['inventory_apply_id'=>$main['inventory_apply_id'], 'status'=>['unconfirmed','confirming']]);
            return [true, ['msg'=>'确认成功']];
        }
        return [false, ['msg'=>'确认失败']];
    }

    /**
     * confirmZP
     * @param mixed $main main
     * @return mixed 返回值
     */
    public function confirmZP($main) {
        $negative_branch_id = json_decode($main['negative_branch_id'], 1);
        if(empty($negative_branch_id)) {
            return [true, ['msg'=>'缺少仓库']];
        }
        kernel::database()->beginTransaction();

        $objInAp = app::get('console')->model('inventory_apply');
        $objInAp->update(['status'=>'confirming','process_date'=>time()], ['inventory_apply_id'=>$main['inventory_apply_id'], 'status'=>['unconfirmed','confirming']]);
        $items = app::get('console')->model('inventory_apply_items')->getList('item_id,bm_id,material_bn,wms_stores,oms_stores,diff_stores,m_type,batch',['inventory_apply_id'=>$main['inventory_apply_id'], 'is_confirm'=>'0', 'm_type'=>'zp']);
        if(empty($items)) {
            kernel::database()->commit();
            app::get('ome')->model('operation_log')->write_log('inventory_apply@console',$main['inventory_apply_id'],"良品确认成功");
            return [true, ['msg'=>'良品确认完成']];
        }
        $data = [
            'task_id' => $main['inventory_apply_id'],
            'task_bn' => $main['inventory_apply_bn'],
            'branch_id' => current($negative_branch_id),
            'negative_branch_id' => $negative_branch_id,
            'operate_type' => 'branch',
            'items' => $items
        ];

        list($rs, $rsData) = kernel::single('console_difference')->insertBill($data);
        if(!$rs) {
            kernel::database()->rollBack();
            $error_msg = '良品差异单新建失败:'.$rsData['msg'];
            app::get('ome')->model('operation_log')->write_log('inventory_apply@console',$main['inventory_apply_id'],$error_msg);

            return [false, ['msg'=>$error_msg]];
        }
        app::get('console')->model('inventory_apply_items')->update(['is_confirm'=>'1'], ['item_id'=>array_column($items, 'item_id')]);
        app::get('ome')->model('operation_log')->write_log('inventory_apply@console',$main['inventory_apply_id'],"良品确认成功");
        kernel::database()->commit();
        return [true, $rsData];
    }

    /**
     * confirmCC
     * @param mixed $main main
     * @return mixed 返回值
     */
    public function confirmCC($main) {
        $negative_cc_branch_id = json_decode($main['negative_cc_branch_id'], 1);
        if(empty($negative_cc_branch_id)) {
            return [true, ['msg'=>'缺少仓库']];
        }
        kernel::database()->beginTransaction();

        $objInAp = app::get('console')->model('inventory_apply');
        $objInAp->update(['status'=>'confirming','process_date'=>time()], ['inventory_apply_id'=>$main['inventory_apply_id'], 'status'=>['unconfirmed','confirming']]);
        $items = app::get('console')->model('inventory_apply_items')->getList('item_id,bm_id,material_bn,wms_stores,oms_stores,diff_stores,m_type,batch',['inventory_apply_id'=>$main['inventory_apply_id'], 'is_confirm'=>'0', 'm_type'=>'cc']);
        if(empty($items)) {
            kernel::database()->commit();
            app::get('ome')->model('operation_log')->write_log('inventory_apply@console',$main['inventory_apply_id'],"残品确认成功");
            return [true, ['msg'=>'良品确认完成']];
        }
        $data = [
            'task_id' => $main['inventory_apply_id'],
            'task_bn' => $main['inventory_apply_bn'],
            'branch_id' => current($negative_cc_branch_id),
            'negative_branch_id' => $negative_cc_branch_id,
            'operate_type' => 'branch',
            'items' => $items
        ];
        list($rs, $rsData) = kernel::single('console_difference')->insertBill($data);
        if(!$rs) {
            kernel::database()->rollBack();

            $error_msg = '残品差异单新建失败:'.$rsData['msg'];
            app::get('ome')->model('operation_log')->write_log('inventory_apply@console',$main['inventory_apply_id'],$error_msg);

            return [false, ['msg'=>$error_msg]];
        }
        app::get('console')->model('inventory_apply_items')->update(['is_confirm'=>'1'], ['item_id'=>array_column($items, 'item_id')]);
        app::get('ome')->model('operation_log')->write_log('inventory_apply@console',$main['inventory_apply_id'],"残品确认成功");
        kernel::database()->commit();
        return [true, $rsData];
    }
}