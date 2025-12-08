<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 基础物料保质期Lib类
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */
class material_storagelife{

    /**
     * 
     * 检查基础物料是否是保质期类型
     * @param Int $id 基础物料ID
     */

    public function checkStorageLifeById($id){
        $basicMConfObj    = app::get('material')->model('basic_material_conf');
        $basicInfo = $basicMConfObj->dump(array('bm_id'=>$id, 'use_expire'=>1), 'bm_id');
        return $basicInfo ? true : false;
    }

    /**
     * 
     * 根据基础物料ID获取相应的保质期配置信息
     * @param Int $id 基础物料ID
     */
    public function getStorageLifeInfoById($id){
        $basicMConfObj    = app::get('material')->model('basic_material_conf');
        $basicInfo = $basicMConfObj->dump(array('bm_id'=>$id, 'use_expire'=>1), '*');
        return $basicInfo ? $basicInfo : [];
    }

    /**
     * 
     * 根据基础物料ID批量获取相应的保质期配置信息
     * @param Array $ids 基础物料ID
     */
    public function getStorageLifeInfoByIds($ids){
        return app::get('material')->model('basic_material_conf')->getList('*', array('bm_id|in' => $ids, 'use_expire' => 1));
    }

    /**
     * 
     * 根据基础物料bn批量获取相应的保质期配置信息
     * @param Array $bns 基础物料bn
     */
    public function getStorageLifeInfoByBns($bns){
        return app::get('material')->model('basic_material_conf')->db->select('SELECT m.material_bn, mc.bm_id, mc.use_expire FROM `sdb_material_basic_material` m LEFT JOIN `sdb_material_basic_material_conf` mc on m.bm_id=mc.bm_id WHERE material_bn in ("' . implode('","', $bns) . '") AND `use_expire`="1"');
    }

    /**
     * 
     * 根据单据ID、类型、仓库ID及物料ID获取相应的批次操作单据
     * @param Int $branch_id 仓库ID
     * @param Int $bill_id 单据ID
     * @param Int $type 单据类型ID
     * @param Int $bm_id 基础物料ID
     * @return Array
     */
    public function getStorageLifeBillById($branch_id, $bill_id, $type, $bm_id){
        $basicMaterialStorageLifeBillsObj = app::get('material')->model('basic_material_storage_life_bills');
        $storageLifeBill = $basicMaterialStorageLifeBillsObj->getList( '*',array('branch_id'=>$branch_id, 'bill_id'=>$bill_id, 'bill_type'=>$type, 'bm_id'=>$bm_id));
        return $storageLifeBill ? $storageLifeBill : '';
    }

    /**
     * 
     * 获取单张发货单预占保质期批次信息
     * @param Int $branch_id 仓库ID
     * @param Int $bill_id 单据ID
     * @return Array
     */
    public function getDlyFreezeSLBillsById($branch_id, $bill_id){
        $basicMaterialStorageLifeBillsObj = app::get('material')->model('basic_material_storage_life_bills');
        $storageLifeBill = $basicMaterialStorageLifeBillsObj->getList( '*',array('branch_id'=>$branch_id, 'bill_id'=>$bill_id, 'bill_type'=>3, 'bill_io_type'=>2), 0, -1, 'bill_id asc, bmslb_id desc');
        return $storageLifeBill ? $storageLifeBill : '';
    }

    /**
     * 
     * 批量获取发货单预占保质期批次信息
     * @param Int $branch_id 仓库ID
     * @param Int $bill_ids 单据ID
     * @return Array
     */
    public function getDlyFreezeSLBillsByIds($branch_id, $bill_ids){
        $basicMaterialStorageLifeBillsObj = app::get('material')->model('basic_material_storage_life_bills');
        $storageLifeBills = $basicMaterialStorageLifeBillsObj->getList( '*',array('branch_id'=>$branch_id, 'bill_id'=>$bill_ids, 'bill_type'=>3, 'bill_io_type'=>2), 0, -1, 'bill_id asc, bmslb_id desc');
        return $storageLifeBills ? $storageLifeBills : '';
    }

    /**
     * 
     * 根据物料ID、仓库ID获取有效的保质期列表信息
     * @param Int $bm_id 基础物料ID
     * @param Int $branch_id 仓库ID
     * @return Array
     */
    public function getStorageLifeBatchList($bm_id, $branch_id){
        $basicMaterialStorageLifeObj = app::get('material')->model('basic_material_storage_life');
        $storageLifeBatch = $basicMaterialStorageLifeObj->getList( '*',array('bm_id'=>$bm_id, 'branch_id'=>$branch_id, 'status'=>1));
        return $storageLifeBatch ? $storageLifeBatch : '';
    }

    /**
     * 根据保质期条码、仓库ID及物料ID检查是否关联有效
     * 
     * @param Int $branch_id
     * @param Int $bm_id
     * @param String $code
     * @return String/Boolean
     */
    public function checkStorageListBatchExist($branch_id, $bm_id, $code, &$msg){
        $basicMaterialStorageLifeObj = app::get('material')->model('basic_material_storage_life');
        $storageLifeBatch = $basicMaterialStorageLifeObj->dump(array('branch_id'=>$branch_id, 'bm_id'=>$bm_id, 'expire_bn'=>$code),'*');
        if(!$storageLifeBatch){
            $msg = '找不到当前的保质期条码';
            return false;
        }

        if($storageLifeBatch['status'] == 2){
            $msg = '该保质期条码已关闭，不可做出入库操作';
            return false;
        }

        return $storageLifeBatch ;
    }

    /**
     * 根据仓库、物料编号查询获取失效的保质期库存总数
     * 
     * @param Int $branch_id
     * @param Int $bm_id
     * @return Int
     */
    public function getExpireStorageLifeStore($branch_id, $bm_id){
        $expire_store = 0;
        $basicMaterialStorageLifeObj = app::get('material')->model('basic_material_storage_life');
        $expireStorageList = $basicMaterialStorageLifeObj->getList('balance_num',array('branch_id'=>$branch_id,'bm_id'=>$bm_id,'balance_num|than'=>0,'quit_date|lthan'=>time()));
        if($expireStorageList){
            foreach($expireStorageList as $expireStorage){
                $expire_store += $expireStorage['balance_num'];
            }
        }

        return $expire_store ;
    }

    /**
     * 根据仓库、物料编号、保质期获取当前保质期的信息
     * 
     * @param Int $branch_id
     * @param Int $bm_id
     * @param String $expire_bn
     * @return Boolean
     */
    public function getStorageLifeBatch($branch_id, $bm_id, $expire_bn){
        $basicMaterialStorageLifeObj = app::get('material')->model('basic_material_storage_life');
        $storageLifeInfo = $basicMaterialStorageLifeObj->getList( '*',array('bm_id'=>$bm_id, 'branch_id'=>$branch_id, 'expire_bn'=>$expire_bn));
        return $storageLifeInfo ? $storageLifeInfo[0] : '';
    }
}