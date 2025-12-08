<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeanalysts_finder_ome_sales{
	var $column_gross_sales = '毛利';

    /**
     * column_gross_sales
     * @param mixed $rows rows
     * @return mixed 返回值
     */
    public function column_gross_sales($rows) {

		return "1.00";
	}

	var $column_gross_sales_rate = '毛利率';

    /**
     * column_gross_sales_rate
     * @param mixed $rows rows
     * @return mixed 返回值
     */
    public function column_gross_sales_rate($rows) {
        #echo "<pre>";
        #print_r($rows);exit;
	}

}