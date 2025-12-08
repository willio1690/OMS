<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_inventory{
    /**
     * 分批盘点
     * 
     */
    function doajax_inventorylist($data,$itemId,&$fail,&$succ,&$fallinfo){
        $oInventory = app::get('taoguaninventory')->model('inventory');
        
        $inventoryObj = kernel::single('taoguaninventory_inventorylist');
        $oInventory_items = app::get('taoguaninventory')->model('inventory_items');
        
        $inventory = $oInventory->db->selectrow('SELECT inventory_type,inventory_bn,branch_id,branch_name,op_id,op_name,confirm_status,inventory_id FROM sdb_taoguaninventory_inventory WHERE inventory_id='.$data['inventory_id']);
        $branch_id = $inventory['branch_id'];
        $inventory_id = $data['inventory_id'];
        $inventory_type = $inventory['inventory_type'];
        
        $branch = $oInventory->db->selectrow('SELECT branch_bn,type FROM sdb_ome_branch WHERE branch_id='.$branch_id);
        $item_id_list = array();
        foreach($itemId as $item_id){
            $item_id = explode('||',$item_id);
            $item_id = $item_id[1];
            $item_id_list[] = $item_id;
        }
        
        //条件加入：bmc.use_expire是否保质期物料
        $sql = 'SELECT i.item_id,i.status,i.bn,i.product_id,i.name,i.actual_num, bp.store as accounts_num, bmc.use_expire 
                FROM sdb_taoguaninventory_inventory_items as i 
                LEFT join sdb_material_basic_material AS a ON i.bn=a.material_bn 
                LEFT JOIN sdb_ome_branch_product as bp ON a.bm_id=bp.product_id 
                LEFT JOIN sdb_material_basic_material_conf AS bmc ON a.bm_id = bmc.bm_id 
                WHERE i.item_id in('.implode(',',$item_id_list).') AND bp.branch_id='.$branch_id.' AND i.inventory_id='.$inventory_id.' AND i.status=\'false\'';
        
        $inventory_items = $oInventory_items->db->select($sql);
        $item = array();
        $numField = $branch['type'] == 'damaged' ? 'defective_num' : 'normal_num';
        $expire_data    = array();
        $batch_item_sql = array();
        foreach ($inventory_items as $inventory_item)
        {
            //保质期物料重新计算账面数量
            if($inventory_item['use_expire'] == '1')
            {
                $inventory_item['accounts_num']	= $inventoryObj->get_accounts_num($inventory_item['product_id'], $branch_id, $inventory_id, $inventory_item['item_id']);
                
                $expire_data['bm_id'][]     = $inventory_item['product_id'];
                $expire_data['item_id'][]   = $inventory_item['item_id'];
            }
            
            $accounts_num = $inventory_item['accounts_num'];
            $shortage_over = $inventory_item['actual_num']-$accounts_num;
            $item_id = $inventory_item['item_id'];
            $item[] = array(
                'product_bn'    => $inventory_item['bn'],
                'totalQty'      => $inventory_item['actual_num'],
                $numField       => $shortage_over,
            );
            $batch_item_sql[] = "(".$item_id.",".$inventory_id.",".$accounts_num.",'true')";
        }

        #批量更新明细状态和账面数量
        if (count($batch_item_sql)>0){
            $update_item_sql = 'INSERT INTO sdb_taoguaninventory_inventory_items(item_id, inventory_id, accounts_num, `status`) VALUES'.implode(',',$batch_item_sql).' ON DUPLICATE KEY UPDATE accounts_num=VALUES(accounts_num),`status`=VALUES(`status`)';
            $oInventory_items->db->exec($update_item_sql);
        }
        $confirm_status = $this->updateInventory($inventory_id);

        $wms_id = kernel::single('ome_branch')->getWmsIdById($inventory['branch_id']);
        $tmp = array(
                'inventory_bn'    => $inventory['inventory_bn'],
                'warehouse'       => $branch['branch_bn'],//仓库编号
                'wms_id'          => $wms_id,
                'wms_bn'          => kernel::single('channel_func')->getWmsBnByWmsId($wms_id),
                'remark'          => $inventory['memo'],
                'autoconfirm'     => $confirm_status == 2 ? 'Y' : 'N',
                'mode'            => '2', #增量
                'item'            => json_encode($item),
        );
        
        $result = kernel::single('wms_event_trigger_inventory')->apply($wms_id, $tmp, true);
        
        //更新保质期批次号库存数量
        if($expire_data){
            $_result    = $inventoryObj->update_storage_life_store($inventory_id, $branch_id, $expire_data);
        }
        
        return true;
    }

    /**
     * 更新盘点确认状态
     */
    function updateInventory($inventory_id){
        $oInventory = app::get('taoguaninventory')->model('inventory');
        $oInventory_items = app::get('taoguaninventory')->model('inventory_items');
        $count_inventory_items = $oInventory_items->count(array('inventory_id'=>$inventory_id,'status'=>'false'));
        $inventory_data = array();
        
        if($count_inventory_items==0){
            $inventory_data['confirm_status'] = 2;
        }else{
            $inventory_data['confirm_status'] = 4;
        }
        $inventory_data['confirm_time'] = time();
        $inventory_data['confirm_op'] = kernel::single('desktop_user')->get_name();

        $oInventory ->update($inventory_data,array('inventory_id'=>$inventory_id));
        return $inventory_data['confirm_status'];
    }
}
?>
