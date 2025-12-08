<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_finder_dailyinventory_items_wmsdinv
{
    public $addon_cols = "outer_stock";

    public $column_outer_stock       = 'WMS库存';
    public $column_outer_stock_order = 70;
    /**
     * column_outer_stock
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_outer_stock($row)
    {
        return $row[$this->col_prefix . 'outer_stock'];
    }
}