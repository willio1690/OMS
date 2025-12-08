<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_mdl_monthly_report_items extends dbeav_model {
    var $has_export_cnf = true;
    var $export_name = '账期核销列表';

    /**
     * modifier_gap
     * @param mixed $col col
     * @param mixed $list list
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function modifier_gap($col,$list,$row){
        if($this->is_export_data) {
            return $col;
        }
        return sprintf('<a href="index.php?app=finance&ctl=monthend_verification&act=base_list&p[0]=%s" target="_blank">%s</a>',$row['id'], $col);
    }

    /**
     * modifier_order_bn
     * @param mixed $col col
     * @param mixed $list list
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function modifier_order_bn($col,$list,$row){
        if($this->is_export_data) {
            return $col;
        }
        return sprintf('<a href="index.php?app=finance&ctl=monthend_verification&act=sale_list&p[0]=%s" target="_blank">%s</a>',$row['id'], $col);
    }
}