<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_finder_dailyinventory_wmsdinv
{
    public $addon_cols = "outer_stock";

    public $column_opt       = '操作';
    public $column_opt_order = 1;
    /**
     * column_opt
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_opt($row)
    {
        $buttons = [];

        $url = sprintf("index.php?app=console&ctl=admin_dailyinventory&p[]=%s&act=wmsItemIndex&finder_vid=%s",$row['id'], $_GET['finder_vid']);


        $buttons['items'] = sprintf('<a href="%s">查看明细</a>',$url);

        return implode(' | ', $buttons);
    }

    public $column_outer_stock       = 'WMS库存';
    public $column_outer_stock_order = 50;
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
