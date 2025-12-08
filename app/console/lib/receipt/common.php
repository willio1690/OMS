<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_receipt_common{

    /**
     * 获取Supplier
     * @param mixed $supplier_id ID
     * @return mixed 返回结果
     */
    public function getSupplier($supplier_id){
        static $supplier;
        if (isset($supplier[$supplier_id])) return $supplier[$supplier_id];
        $supperObj = app::get('purchase')->model('supplier');
        $rows = $supperObj->dump(array('supplier_id'=>$supplier_id),'name');
        if ($rows){
            $supplier[$supplier_id] = $rows['name'];
            return $supplier[$supplier_id];
        }else{
            return array();
        }
    }

    /**
     * 获取Products
     * @param mixed $bn bn
     * @return mixed 返回结果
     */
    public function getProducts($bn){
        static $products;
        if (isset($products[$bn])) return $products[$bn];
        
        $basicMaterialObj    = app::get('material')->model('basic_material');
        $basicMaterialLib    = kernel::single('material_basic_material');
        
        $bm_ids    = $basicMaterialObj->dump(array('material_bn'=>$bn), 'bm_id');
        $rows      = $basicMaterialLib->getBasicMaterialExt($bm_ids['bm_id']);
        
        if ($rows){
            $rows['product_id'] = $bm_ids['bm_id'];
            $products[$bn] = $rows;
            return $products[$bn];
        }else{
            return array();
        }
    }

    function getIostockList($type_id,$obj_id){
        $db = kernel::database();
        $sql = "SELECT i.product_id,i.bn,i.normal_num FROM sdb_taoguaniostockorder_iso s LEFT JOIN sdb_taoguaniostockorder_iso_items as i ON s.iso_id=i.iso_id WHERE s.type_id='".$type_id."' AND s.original_id=".$obj_id;
        
        $iso_items = $db->select($sql);
        $items = array();
        foreach ($iso_items as $item){
            $items[] = $item['product_id'];
        }
        return $items;
    }
}