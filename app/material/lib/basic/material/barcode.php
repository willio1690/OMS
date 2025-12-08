<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 基础物料条码(SKU码)Lib类
 * @author xiayuanjun@shopex.cn
 * @version 1.0
 */
class material_basic_material_barcode{

    function __construct(){
        $this->_materialBarcodeObj = app::get('material')->model('barcode');

        $this->_basicMaterialObj = app::get('material')->model('basic_material');
        $this->_materialExtObj   = app::get('material')->model('basic_material_ext');
    }

    /**
     *
     * 根据基础物料查询对应的基础物料
     * @param Int $bm_id
     * @return String
     */
    public function getBarcodeById($bm_id){
        $barcodeInfo = $this->_materialBarcodeObj->getList('code',array('bm_id'=>$bm_id),0,1);

        return $barcodeInfo ? $barcodeInfo[0]['code'] : '';
        
    }

    /**
     *
     * 根据条码查询对应的基础物料
     * @param Int $barcode
     * @return String
     */
    public function getIdByBarcode($barcode){
        $barcodeInfo = $this->_materialBarcodeObj->getList('bm_id',array('code'=>$barcode),0,1);
        return $barcodeInfo ? $barcodeInfo[0]['bm_id'] : '';
    }

    /**
     *
     * 根据条形码获取所有关联的基础物料bm_id
     * @param string $code
     * @return Array
     */
    public function getBmidListByBarcode($barcode)
    {
        $barcodeInfo = $this->_materialBarcodeObj->getList('bm_id', array('code'=>$barcode), 0, -1);

        $bm_ids    = array();
        foreach ($barcodeInfo as $key => $val)
        {
            $bm_ids[$val['bm_id']]    = $val['bm_id'];
        }

        return $bm_ids;
    }

    /**
     *
     * 根据条形码获取所有关联的基础物料bm_id
     * @param string $code
     * @return Array
     */
    public function getBmidListByFilter($filter,&$code_list)
    {
        $barcodeInfo = $this->_materialBarcodeObj->getList('*', $filter, 0, -1);

        $bm_ids    = array();
        foreach ($barcodeInfo as $key => $val)
        {
            $bm_ids[$val['bm_id']]    = $val['bm_id'];
            $code_list[$val['bm_id']]    = $val['code'];
        }

        return $bm_ids;
    }
    
    /**
     * 批量通过barcode条形码获取基础物料列表
     *
     * @param $barcodes array 条形码列表
     * @return void
     */
    public function getBmListByBarcode($barcodes, $fields='bm_id,material_bn,type')
    {
        //barcode
        $barcodeList = $this->_materialBarcodeObj->getList('bm_id,code', array('code'=>$barcodes), 0, -1);
        if(empty($barcodeList)){
            return [];
        }
        
        //bm_id
        $bmIds = array_column($barcodeList, 'bm_id');
        
        //files
        if(empty($fields)){
            $fields = '*';
        }
        
        $bmList = $this->_basicMaterialObj->getList($fields, array('bm_id'=>$bmIds));
        if(empty($bmList)){
            return [];
        }
        
        $bmList = array_column($bmList, null, 'bm_id');
        
        //format
        $dataList = [];
        foreach ($barcodeList as $barKey => $barInfo)
        {
            $bm_id = $barInfo['bm_id'];
            $barcode = $barInfo['code'];
            
            //check
            if(!isset($bmList[$bm_id])){
                continue;
            }
            
            //barcode
            $barInfo['barcode'] = $barcode;
            
            //data
            $dataList[$barcode] = array_merge($barInfo, $bmList[$bm_id]);
        }
        
        return $dataList;
    }
}
