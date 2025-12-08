<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class wms_event_trigger_otherinstorage extends wms_event_trigger_stockinabstract{

    function getStockInData($data){
        $iso_id = $data['iso_id'];
        $oIso = app::get('taoguaniostockorder')->model("iso");
        $oIsoItems = app::get('taoguaniostockorder')->model("iso_items");
        $iostockdataObj = kernel::single('wms_iostockdata');
        $Iso = $oIso->dump(array('iso_id'=>$iso_id),'iso_bn,branch_id,type_id,memo');
        $oSupplier = app::get('purchase')->model("supplier");
        $oBranch = app::get('ome')->model("branch");
        $supplier = $oSupplier->supplier_detail($Iso['supplier_id'],'bn');
        $branch = $iostockdataObj->getBranchByid($Iso['branch_id']);
        $iso_items = $oIsoItems->getList('product_id,bn,product_name,normal_num,defective_num,nums',array('iso_id'=>$iso_id));
        $data = array();
        $type_id = $Iso['type_id'];
        if ($type_id=='4' || $type_id=='40'){//调拨出入库
            $io_type = 'ALLCOATE';
        }else{//其他入库
            $io_type = 'OTHER';
        }
        
        $data['io_type'] = $io_type;//类型
        $data['io_bn'] = $Iso['iso_bn'];//类型
        $data['branch_id'] = $Iso['branch_id'];
        $data['io_source'] = 'selfwms';//来源
        $data['io_status'] = 'FINISH';
        $data['branch_bn'] = $branch['branch_bn'];
        $data['supplier_bn'] = $supplier['bn'];
        $data['memo'] = $Iso['memo'];

        $life_bills = $this->getStorageLifeList($Iso['branch_id'], $iso_id,$Iso['type_id']);

        $batchs = [];

        foreach($life_bills as $v){
            $expire_bn = $v['expire_bn'];
            $storagelife = $this->getlifedetail($Iso['branch_id'],$v['bm_id'],$expire_bn);
           
            $productDate = $storagelife['production_date'] ? date('Y-m-d',$storagelife['production_date']) : '';
            $expireDate = $storagelife['expiring_date'] ? date('Y-m-d',$storagelife['expiring_date']) : '';
            $batchs[$v['bm_id']][$v['expire_bn']] = array(
              
                'purchase_code'     => $v['expire_bn'],

                'product_time'      => $storagelife['production_date'],
                'expire_time'       => $storagelife['expiring_date'],
                'normal_defective'  => 'normal',
                'num'               => $v['nums'],
               
            );

           
        }
        //批次信息
        foreach($iso_items as $ik=>$iv){
            $iso_items[$ik]['normal_num'] = $iv['nums'];#接收处区分
            $iso_items[$ik]['num'] = $iv['nums'];#
            if($batchs[$iv['product_id']]){
                $iso_items[$ik]['batch'] = array_values($batchs[$iv['product_id']]);
            }
            
        }
        $data['items'] =$iso_items;
        
        return $data;
    } 



    /**
     * 获取StorageLifeList
     * @param mixed $branch_id ID
     * @param mixed $bill_id ID
     * @param mixed $type_id ID
     * @return mixed 返回结果
     */
    public function getStorageLifeList($branch_id, $bill_id,$type_id){
        $basicMaterialStorageLifeBillsObj = app::get('material')->model('basic_material_storage_life_bills');
        $storageLifeBills = $basicMaterialStorageLifeBillsObj->getList( '*',array('branch_id'=>$branch_id, 'bill_id'=>$bill_id, 'bill_type'=>$type_id, 'bill_io_type'=>1), 0, -1, 'bill_id asc, bmslb_id desc');



        return $storageLifeBills ? $storageLifeBills : '';
    }

    /**
     * 获取lifedetail
     * @param mixed $branch_id ID
     * @param mixed $bm_id ID
     * @param mixed $expire_bn expire_bn
     * @return mixed 返回结果
     */
    public function getlifedetail($branch_id,$bm_id,$expire_bn){

        $basicMaterialStorageLifeObj = app::get('material')->model('basic_material_storage_life');
        $storageLifeBatch = $basicMaterialStorageLifeObj->dump(array('branch_id'=>$branch_id, 'bm_id'=>$bm_id, 'expire_bn'=>$expire_bn),'production_date,expire_bn,expiring_date');

        return $storageLifeBatch;


    }
}

?>
