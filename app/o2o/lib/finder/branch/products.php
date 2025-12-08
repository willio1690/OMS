<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_finder_branch_products extends console_finder_branch_product
{
    var $column_branch_name = '门店名称';

    function __construct()
    {
        parent::__construct();
        $this->column_branch_name = '门店名称';
    }
    
    /**
     * 货品重量字段
     */
    var $column_weight = '货品重量(g)';
    var $column_weight_width = 100;
    var $column_weight_order = 35;
    function column_weight($row, $list)
    {
        return $row['weight'];
    }
    
    /**
     * 包装单位字段
     */
    var $column_unit = '包装单位';
    var $column_unit_width = 100;
    var $column_unit_order = 36;
    function column_unit($row, $list)
    {
        return $row['unit'];
    }
}
?>


