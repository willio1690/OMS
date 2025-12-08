<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 码库Lib类
 * @author xiayuanjun@shopex.cn
 * @version 1.0
 */
class material_codebase {

    /**
     * 获取特性列表方法
     * 
     * @param Null
     * @return Array
     */

    public function getCodeList(){
        return array(
            array('type'=>1,'name'=>'条码'),
            array('type'=>2,'name'=>'批次'),
        );
    }

    /**
     * 获取条码的类型值
     * 
     * @param Null
     * @return Int
     */
    public static function getBarcodeType(){
        return 1;
    }

    /**
     * 获取条码的类型值
     * 
     * @param Null
     * @return Int
     */
    public static function getStorageListType(){
        return 2;
    }

    /**
     * 根据保质期条码检查是否是这个物料的
     * 
     * @param String $code
     * @return Boolean
     */
    public function checkCodeExist($code){
        $codebaseObj = app::get('material')->model('codebase');
        $code_info = $codebaseObj->getList('code',array('code'=>$code));
        return $code_info ? true : false;
    }

    /**
     * 根据保质期条码检查是否是这个物料的
     * 
     * @param Int $bm_id
     * @param String $code
     * @return Boolean
     */
    public function checkBmHasThisStorageListBn($bm_id, $code){
        $codebaseObj = app::get('material')->model('codebase');
        $code_info = $codebaseObj->getList('code',array('type'=>2,'code'=>$code,'bm_id'=>$bm_id));
        return $code_info ? true : false;
    }

    public static function getBarcodeBybn($bn){
        $materialObj= app::get('material')->model('basic_material');
        $codebaseObj = app::get('material')->model('codebase');
        if (!is_array($bn)) {
            $material = $materialObj->dump(array('material_bn'=>$bn),'bm_id');
            if($material){
                $code_info = $codebaseObj->dump(array('type'=>1,'bm_id'=>$material['bm_id']),'code');
                return $code_info['code'];
            }
        } else {
            $list = [];
            $materialList = $materialObj->getList('bm_id, material_bn', ['material_bn|in'=>$bn]);
            if (!$materialList) {
                return $list;
            }

            $materialList = array_column($materialList, 'bm_id', 'material_bn');
            $codeList = $codebaseObj->getList('bm_id, code', ['type'=>1,'bm_id'=>$materialList]);
            $codeList = array_column($codeList, 'code', 'bm_id');
            if (!$codeList) {
                return $list;
            }

            foreach ($bn as $bm_bn) {
                $bm_id = $materialList[$bm_bn];
                if (!$bm_id) {
                    continue;
                }
                $code = $codeList[$bm_id];
                if (!$code) {
                    continue;
                }
                $list[$bm_bn] = $code;
            }
            return $list;
        }
    }
    
    public static function getBnBybarcode($barcode){
        $materialObj= app::get('material')->model('basic_material');
        $codebaseObj = app::get('material')->model('codebase');
        $code_info = $codebaseObj->dump(array('type'=>1,'code'=>$barcode),'bm_id');
         
        if($code_info){
            $material = $materialObj->dump(array('bm_id'=>$code_info['bm_id']),'material_bn');
             
            return $material['material_bn'];
        }
    }

    /**
     * 获取BarcodeBySmbn
     * @param mixed $sm_bn sm_bn
     * @param mixed $shop_id ID
     * @param mixed $sales_material_type sales_material_type
     * @return mixed 返回结果
     */
    public function getBarcodeBySmbn($sm_bn='', $shop_id='', $sales_material_type = '1'){

        $barcode = '';
        switch ($sales_material_type) {
            case '1':
                $salesMLib = kernel::single('material_sales_material');
                $smInfo = $salesMLib->getSalesMByBn($shop_id, $sm_bn);
                if (!$smInfo) {
                    break;
                }
                $bmIds = $salesMLib->getBmIdsBySmIds($smInfo['sm_id']);
                $bmIds = $bmIds[$smInfo['sm_id']];
                if (is_array($bmIds)) {
                    $bmIds = $bmIds[0];
                }
                if (!$bmIds) {
                    break;
                }
                $codebaseObj = app::get('material')->model('codebase');
                $codeInfo = $codebaseObj->db_dump(['type'=>1,'bm_id'=>$bmIds]);
                $barcode = $codeInfo['code'];
                break;

            default:
                break;
        }
        return $barcode;
    }
    
    /**
     * 批量通过基础物料ID获取关联的条形码列表
     * 
     * @param $bmIds
     * @return void
     */
    public function getBarcodeByBmIds($bmIds)
    {
        $codebaseObj = app::get('material')->model('codebase');
        
        //获取条码的类型值
        $code_type = $this->getBarcodeType();
        
        //条形码列表
        $filter = array(
            'bm_id' => $bmIds,
            'type' => $code_type,
        );
        $barcodeList = $codebaseObj->getList('bm_id,code', $filter);
        $barcodeList = array_column($barcodeList, null, 'bm_id');
        
        return $barcodeList;
    }
    
    /**
     * 获取基础物料对应条形码
     * 
     * @param intval $bm_id
     * @return string
     */
    public function getMergeMaterialCodes($materialList)
    {
        $codebaseObj = app::get('material')->model('codebase');
        
        //check
        if(empty($materialList)){
            return false;
        }
        
        //bm_id
        $materialList = array_column($materialList, null, 'bm_id');
        $bmIds = array_keys($materialList);
        
        //获取条码的类型值
        $code_type = $this->getBarcodeType();
        
        //条形码列表
        $filter = array(
            'bm_id' => $bmIds,
            'type' => $code_type,
        );
        $codeList = $codebaseObj->getList('bm_id,code', $filter);
        $codeList = array_column($codeList, null, 'bm_id');
        
        //format
        foreach ($materialList as $bm_id => $bmInfo)
        {
            $barcode = '';
            if(isset($codeList[$bm_id])){
                $barcode = $codeList[$bm_id]['code'];
            }
            
            $materialList[$bm_id]['barcode'] = $barcode;
        }
        
        return $materialList;
    }
}
