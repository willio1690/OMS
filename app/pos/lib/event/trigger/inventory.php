<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class pos_event_trigger_inventory
{
    /**
     *
     * 盘点单审批
     * @param 
     */
    public function check($inventory_id)
    {

        
        $inventoryMdl = app::get('o2o')->model('inventory');

        $inventorys = $inventoryMdl->dump(array('inventory_id'=>$inventory_id),'inventory_bn,branch_id,inventory_id');
        $store_id = kernel::single('ome_branch')->isStoreBranch($inventorys['branch_id']);
        $params = array(
            'inventory_bn'      =>  $inventorys['inventory_bn'],
            
        );

        $channel_type = 'store';
        $channel_id = $store_id;

        $result = kernel::single('erpapi_router_request')->set($channel_type,$channel_id)->inventory_check($params);


        if($result['rsp'] == 'succ'){
            $updateData = array(
                'sync_status'=>'1',

            );
            $rs = [true,'成功'];
        }else{
            $updateData = array(
                'sync_status'=>'2',

            );
            $rs = [false,'失败'];
        }
        //$inventoryMdl->update($updateData,array('store_id'=>$store_id));
        return $rs;
    }
    

    /**
     *
     * 盘点单取消
     * @param 
     */
    public function cancel($inventory_id)
    {

        
        $inventoryMdl = app::get('o2o')->model('inventory');

        $inventorys = $inventoryMdl->dump(array('inventory_id'=>$inventory_id),'inventory_bn,branch_id,inventory_id');
        $store_id = kernel::single('ome_branch')->isStoreBranch($inventorys['branch_id']);
        $params = array(
            'inventory_bn'      =>  $inventorys['inventory_bn'],
            
        );

        $channel_type = 'store';
        $channel_id = $store_id;

        $result = kernel::single('erpapi_router_request')->set($channel_type,$channel_id)->inventory_cancel($params);


        if($result['rsp'] == 'succ'){
            $updateData = array(
                'sync_status'=>'1',

            );
            $rs = [true,'成功'];
        }else{
            $updateData = array(
                'sync_status'=>'2',

            );
            $rs = [false,'失败'];
        }
        //$inventoryMdl->update($updateData,array('store_id'=>$store_id));
        return $rs;
    }

    /**
     * 盘点调整单创建
     */
    public function addAdjust($id){
        $differenceMdl = app::get('console')->model('difference');
        $diff = $differenceMdl->db_dump($id);
        $dfiObj = app::get('console')->model('difference_items');
        $items = $dfiObj->getList('*', ['diff_id'=>$id]);
        $task_id = $diff['task_id'];
        $task_bn = $diff['task_bn'];

        $invObj = app::get('o2o')->model('inventory');
        $inventory = $invObj->db_dump(array('inventory_id'=>$task_id,'inventory_bn'=>$task_bn),'physics_id');

        $physics_id = $inventory['physics_id'];

        $storeMdl = app::get('o2o')->model('store');
        $stores = $storeMdl->db_dump(array('store_id'=>$physics_id),'store_bn');

        $branchMdl = app::get('ome')->model('branch');
        $branch_id = $diff['branch_id'];
        $branchs = $branchMdl->db_dump(array('branch_id'=>$branch_id, 'check_permission'=> 'false'), 'branch_id,name,branch_bn,b_type');

        $store_id = kernel::single('ome_branch')->isStoreBranch($branch_id);
        $params = array(
            'diff_bn'   =>  $diff['diff_bn'],
            'store_bn'  =>  $stores['store_bn'],
            'branch_bn' =>  $branchs['branch_bn'],
            'at_time'   =>  $diff['at_time'],  
            'inventory_bn'=> $task_bn, 
            'items'     =>  $items,
        );

        $channel_type = 'store';
        $channel_id = $store_id;

        $rs = kernel::single('erpapi_router_request')->set($channel_type,$channel_id)->adjust_create($params);
        return $rs;
    }
}
