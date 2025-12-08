<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_warehouse{


    /**
     * 区域仓同步
     * @param  
     */
    public function sync($id,$shop_ids){

        $warehouse = $this->getWarehouse($id);
        foreach((array)$shop_ids as $shop_id){

            $this->syncWarehouse($shop_id,$warehouse);
            $this->bindAddr($shop_id,$warehouse);
        }
        
    }



    public function syncWarehouse($shop_id,$warehouse){
        $params = array(
            'out_warehouse_id'  =>  $warehouse['branch_bn'],
            'name'              =>  $warehouse['warehouse_name'],
        );
        $warehouseObj = app::get('logisticsmanager')->model('warehouse');
        $rs = kernel::single('erpapi_router_request')->set('shop', $shop_id)->branch_createWarehouse($params);
        $sync_warehouse = '2';
        if($rs['rsp'] == 'succ'){
            
            $sync_warehouse = '1';
            $this->saveWarehouseShop(array('outwarehouse_id'=>$rs['data'],'branch_id'=>$warehouse['branch_id'],'shop_id'=>$shop_id,'warehouse_id'=>$warehouse['id'],'sync_warehouse'=>$sync_warehouse));
        }
      

    }


    /**
     * bindAddr
     * @param mixed $shop_id ID
     * @param mixed $warehouse warehouse
     * @return mixed 返回值
     */
    public function bindAddr($shop_id,$warehouse){
        
        $addr = $this->getShopAddr($warehouse);

        $params = array(
            'addr'              =>  $addr,
            'out_warehouse_id'  =>  $warehouse['branch_bn'],

        );
        $rs = kernel::single('erpapi_router_request')->set('shop', $shop_id)->branch_bindWarehouse($params);
        $sync_status = '2';
        if($rs['rsp'] == 'succ'){
            $sync_status = '1';

            //保存绑定结果
            $this->saveWarehouseShop(array('branch_id'=>$warehouse['branch_id'],'shop_id'=>$shop_id,'warehouse_id'=>$warehouse['id'],'sync_status'=>$sync_status));
        }

    }

    /**
     * 获取Warehouse
     * @param mixed $id ID
     * @return mixed 返回结果
     */
    public function getWarehouse($id){
        $warehouseObj = app::get('logisticsmanager')->model('warehouse');
        $warehouse = $warehouseObj->dump(array('id'=>$id),'*');
        return $warehouse;
    }

    /**
     * 保存WarehouseShop
     * @param mixed $data 数据
     * @return mixed 返回操作结果
     */
    public function saveWarehouseShop($data){
        $warehouseshopObj = app::get('logisticsmanager')->model('warehouse_shop');
        $warehouseshop = $warehouseshopObj->dump(array('warehouse_id'=>$data['warehouse_id'],'shop_id'=>$data['shop_id']),'warehouse_id');
        $insert_data = array(
            'warehouse_id'  =>  $data['warehouse_id'],
            'shop_id'       =>  $data['shop_id'],
            'branch_id'     =>  $data['branch_id'],
        );
        if ($data['sync_status']) $insert_data['sync_status'] = $data['sync_status'];
        if ($data['sync_warehouse']) $insert_data['sync_warehouse'] = $data['sync_warehouse'];
        if ($data['outwarehouse_id']) $insert_data['outwarehouse_id'] = $data['outwarehouse_id'];
        if ($warehouseshop){
            
            $warehouseshopObj->update($insert_data,array('warehouse_id'=>$data['warehouse_id'],'shop_id'=>$data['shop_id']));
        }else{
            $warehouseshopObj->save($insert_data);
        }
    }

    /**
     * 检查Region
     * @param mixed $id ID
     * @param mixed $msg msg
     * @return mixed 返回验证结果
     */
    public function checkRegion($id,&$msg){
        $warehouse = $this->getWarehouse($id);
        $regionsObj = app::get('eccommon')->model('platform_regions');

        $region_ids = $warehouse['region_ids'];
        if($region_ids){
            if (!empty($warehouse['one_level_region_names'])) {
                $regionIds = explode(';', $region_ids);
                $region_ids       = implode(',',$regionIds);
            } else {
                $region_ids = explode(',',$region_ids);
            }
            $regionsList = $regionsObj->getlist('local_region_id',array('shop_type'=>'luban','local_region_id'=>$region_ids));

            $regionsList = array_column($regionsList, 'local_region_id');
            foreach($region_ids as $region_id){
                if(!in_array($region_id,$regionsList)){
                    $msg = $region_id.',';
                    return false;
                }
            }
            return true;

        }else{
            return false;
        }
    }
    
    /**
     * 获取ShopAddr
     * @param mixed $warehouse warehouse
     * @return mixed 返回结果
     */
    public function getShopAddr($warehouse)
    {
        $regionIds = $warehouse['region_ids'];
        $oldData = true;
        if (!empty($warehouse['one_level_region_names'])) {
            $regionIds = explode(',', $regionIds);
            $ids       = implode(',',$regionIds);
            $oldData = false;
        } else {
            $ids = $regionIds;
        }
        
        $region_ids = explode(',', $ids);

        $regionsObj = app::get('eccommon')->model('platform_regions');
        
        $regionsList = $regionsObj->getlist('outregion_id,local_region_id',
            array('shop_type' => 'luban', 'local_region_id' => $region_ids, 'mapping' => '1'));
        
        $regionsList = array_column($regionsList, null,'local_region_id');
    
        $addrList = array();
        if (!is_array($regionIds)) {
            $regionIds = explode(',',$regionIds);
        }

        foreach($regionIds as $v){
            if ($oldData) {
                $addrList[]['addr_id1']=(int)$regionsList[$v]['outregion_id'];
            }else{
                list($province, $city, $county) = explode(',',$v);
                $addrList[]= [
                    'addr_id1' =>(int)$regionsList[$province]['outregion_id'],
                    'addr_id2' =>(int)$regionsList[$city]['outregion_id'],
                    'addr_id3' =>(int)$regionsList[$county]['outregion_id'],
                ];
            }
           
        }
        
        return json_encode($addrList);
    }
}

?>