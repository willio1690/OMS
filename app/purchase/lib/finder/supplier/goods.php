<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class purchase_finder_supplier_goods
{
    var $addon_cols = 'supplier_id,bm_id';
    
    var $column_supplier_bn = '供应商编码';
    var $column_supplier_bn_width = '120';
    var $column_supplier_bn_order = 10;
    function column_supplier_bn($row)
    {
        $supplierObj    = app::get('purchase')->model('supplier');
        $data    = $supplierObj->dump(array('supplier_id'=>$row[$this->col_prefix.'supplier_id']), 'bn');
        
        return $data['bn'];
    }
    
    var $column_supplier_name = '供应商';
    var $column_supplier_name_width = '150';
    var $column_supplier_name_order = 15;
    function column_supplier_name($row)
    {
        $supplierObj    = app::get('purchase')->model('supplier');
        $data    = $supplierObj->dump(array('supplier_id'=>$row[$this->col_prefix.'supplier_id']), 'name');
        
        return $data['name'];
    }
    
    var $column_material_bn = '基础物料编码';
    var $column_material_bn_width = '200';
    var $column_material_bn_order = 20;
    function column_material_bn($row)
    {
        $materialObj    = app::get('material')->model('basic_material');
        $data    = $materialObj->dump(array('bm_id'=>$row[$this->col_prefix.'bm_id']), 'material_bn');
        
        return $data['material_bn'].$row['bm_id'];
    }
    
    var $column_material_name = '基础物料名称';
    var $column_material_name_width = '150';
    var $column_material_name_order = 25;
    function column_material_name($row)
    {
        $materialObj    = app::get('material')->model('basic_material');
        $data    = $materialObj->dump(array('bm_id'=>$row[$this->col_prefix.'bm_id']), 'material_name');
        
        return $data['material_name'].$row['bm_id'];
    }
}
?>