<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/12/7 10:59:28
 * @describe: 类
 * ============================
 */
class financebase_finder_expenses_export_log {

    public $addon_cols = 'export_type,filter';

    public $column_trade = "账单日期";
    public $column_trade_width = "200";
    /**
     * column_trade
     * @param mixed $row row
     * @return mixed 返回值
     */

    public function column_trade($row) {
        $filter = json_decode($row[$this->col_prefix . 'filter'], 1);
        return $filter['time_from'] . ' ~ ' . $filter['time_to'];
    }

    public $column_split = "拆分日期";
    public $column_split_width = "200";
    /**
     * column_split
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_split($row) {
        $filter = json_decode($row[$this->col_prefix . 'filter'], 1);
        return $filter['split_time_from'] . ' ~ ' . $filter['split_time_to'];
    }

    public $column_category = "费用项";
    public $column_category_width = "100";
    /**
     * column_category
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_category($row) {
        $exportType = $row[$this->col_prefix . 'export_type'];
        if($exportType == 'items') {
            return '-';
        }
        $filter = json_decode($row[$this->col_prefix . 'filter'], 1);
        return $filter['bill_category'] ? : '全部';
    }
    
    public $column_material = "基础物料编码";
    public $column_material_width = "100";
    /**
     * column_material
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_material($row) {
        $exportType = $row[$this->col_prefix . 'export_type'];
        if($exportType == 'main') {
            return '-';
        }
        $filter = json_decode($row[$this->col_prefix . 'filter'], 1);
        return $filter['material_bn'] ? : '-';
    }
}