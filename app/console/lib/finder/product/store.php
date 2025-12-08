<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_finder_product_store
{
    public $addon_cols = 'branch_id,bm_id';

    //线上在售冻结库存
    public $column_store_freeze = '线上在售冻结';
    public $column_store_freeze_width = 120;
    public $column_store_freeze_order = 90;
    /**
     * column_store_freeze
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_store_freeze($row)
    {
        $basicMStockFreezeLib = kernel::single('material_basic_material_stock_freeze');
        $store_freeze  = $basicMStockFreezeLib->getO2oBranchFreeze($row[$this->col_prefix . 'bm_id'], $row[$this->col_prefix . 'branch_id']);

        return $store_freeze;
    }

    public $column_store_bn = '关联门店编码';
    /**
     * column_store_bn
     * @param mixed $row row
     * @param mixed $list list
     * @return mixed 返回值
     */
    public function column_store_bn($row, $list)
    {
        $store = $this->_getStore($row[$this->col_prefix . 'branch_id'], $list);

        return $store['store_bn'];
    }

    private function _getStore($branch_id, $list)
    {
        static $store_list;

        if (isset($store_list)) {
            return $store_list[$branch_id];
        }

        $filter['branch_id'] = array_column($list, $this->col_prefix . 'branch_id');

        $store_list = app::get('o2o')->model('store')->getList('store_bn,branch_id', $filter);

        $store_list = array_column($store_list, null, 'branch_id');

        return $store_list[$branch_id];
    }
}
