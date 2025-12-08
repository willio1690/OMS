<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/12/3 18:00:11
 * @describe: 费用均摊明细
 * ============================
 */
class financebase_finder_expenses_split {
    public $addon_cols = 'bm_id';

    public $column_skucode = "基础物料编码";
    public $column_skucode_width = "80";
    /**
     * column_skucode
     * @param mixed $row row
     * @return mixed 返回值
     */

    public function column_skucode($row) {
        $bmId = $row[$this->col_prefix . 'bm_id'];
        $bm = app::get('material')->model('basic_material')->db_dump(array('bm_id'=>$bmId), 'material_bn');
        return (string)$bm['material_bn'];
    }
}