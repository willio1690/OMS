<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/9/7 15:49:19
 * @describe: 经销商货品
 * ============================
 */
class dealer_finder_goods {
    private $business;
    private $material;

    public $addon_cols = "bs_id,bm_id";
    public $column_bs_bn = "经销商编码";
    public $column_bs_bn_width = 120;
    public $column_bs_bn_order = 1;
    /**
     * column_bs_bn
     * @param mixed $row row
     * @return mixed 返回值
     */

    public function column_bs_bn($row){
        $bs_id = $row[$this->col_prefix.'bs_id'];
        if(!isset($this->business[$bs_id])) {
            $this->business[$bs_id] = $this->_getBusiness($bs_id);
        }
        return $this->business[$bs_id]['bs_bn'];
    }
    public $column_name = "经销商名称";
    public $column_name_width = 120;
    public $column_name_order = 2;
    /**
     * column_name
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_name($row){
        $bs_id = $row[$this->col_prefix.'bs_id'];
        if(!isset($this->business[$bs_id])) {
            $this->business[$bs_id] = $this->_getBusiness($bs_id);
        }
        return $this->business[$bs_id]['name'];
    }

    private function _getBusiness($bs_id) {
        return app::get('dealer')->model('business')->db_dump(array('bs_id'=>$bs_id), 'bs_bn,name');
    }

    public $column_material_bn = "基础物料编码";
    public $column_material_bn_order = 3;
    /**
     * column_material_bn
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_material_bn($row) {
        $bm_id = $row[$this->col_prefix . 'bm_id'];
        if(!isset($this->material[$bm_id])) {
            $this->material[$bm_id] = $this->_getMaterial($bm_id);
        }
        return $this->material[$bm_id]['material_bn'];
    }

    public $column_material_name = "基础物料名称";
    public $column_material_name_order = 3;
    /**
     * column_material_name
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_material_name($row) {
        $bm_id = $row[$this->col_prefix . 'bm_id'];
        if(!isset($this->material[$bm_id])) {
            $this->material[$bm_id] = $this->_getMaterial($bm_id);
        }
        return $this->material[$bm_id]['material_name'];
    }

    private function _getMaterial($bm_id) {
        return app::get('material')->model('basic_material')->db_dump(array('bm_id'=>$bm_id), 'material_bn, material_name');
    }
}