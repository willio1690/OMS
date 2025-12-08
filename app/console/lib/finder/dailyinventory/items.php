<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_finder_dailyinventory_items
{
    
    public $column_material_name       = '物料名称';
    public $column_material_name_width = 200;
    public $column_material_name_order = 30;
    /**
     * column_material_name
     * @param mixed $row row
     * @param mixed $list list
     * @return mixed 返回值
     */
    public function column_material_name($row,$list)
    {
        $material = $this->_getMaterial($row['material_bn'], $list);
        return $material['material_name'];
    }
    
    private function _getMaterial($material_bn, $list)
    {
        static $material;
        if (isset($material[$material_bn])) {
            return $material[$material_bn];
        }
        $basicMaterialObj = app::get('material')->model('basic_material');
        $material_bns = array_column($list,'material_bn');
        $materialList = $basicMaterialObj->getList('bm_id,material_bn,material_name', ['material_bn'=>$material_bns]);
        $materialList = array_column($materialList,null,'material_bn');
        foreach ($list as $row) {
            $material[$row['material_bn']]['material_name'] = $materialList[$row['material_bn']]['material_name'] ?? '';
        }
        return $material[$material_bn];
    }
}
