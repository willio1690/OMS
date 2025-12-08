<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 唯品会出库单模拟回传
 */
class omevirtualwms_finder_vopstockout{
    var $column_control = '操作';
	var $column_control_width = 100;
    function column_control($row){
        $flag = "vopstockout";
        return '<a  href="index.php?app=omevirtualwms&ctl=admin_wms&act=getinfo&p[0]='.$row['stockout_no'].'&p[1]='.$flag.'">开始模拟</a>';
    }
    
    function row_style($row)
    {
        if (in_array($row['stockout_no'],app::get('omevirtualwms')->model('vopstockout')->queue)) {
            return "\" style=\"background-color:#ffe3e7;\"";
        }
    }
}
