<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class material_info{

    /**
     * 物料相关信息
     */
    public function get_material_info($keyword)
    {
        $material_basic = app::get('material')->model('basic_material');
        $material_ext_obj = app::get('material')->model('basic_material_ext');
        $ome_brand_obj = app::get('ome')->model('brand');
        $basicMaterialBarcode = kernel::single('material_basic_material_barcode');


        $bm_id = $material_basic->getList('bm_id',array('material_bn' => $keyword), 0, 1);
        if(empty($bm_id)){
            $bm_id = $material_basic->getList('bm_id',array('material_name' => $keyword), 0, 1);
            if(empty($bm_id)){
                $bm_id = $basicMaterialBarcode->getIdByBarcode($keyword);
            }
        }
        if(empty($bm_id)){
            return false;
        }
        $material_ext = $material_ext_obj->getList('*', array('bm_id' => $bm_id[0]['bm_id']), 0, 1);
        $brands = $ome_brand_obj->getList('brand_name', array('brand_id' => $material_ext[0]['brand_id']));

        $tmp_array = array();
        $tmp_array['brand_id'] = $material_ext[0]['brand_id'];
        $tmp_array['brand_name'] = $brands[0]['brand_name'];
        $tmp_array['specifications'] = $material_ext[0]['specifications'];

        return $tmp_array;
    }
}