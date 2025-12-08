<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omevirtualwms_finder_stockin{

    
    var $column_control = '操作';
	var $column_control_width = 100;
    function column_control($row){
    	$flag = "stockin";
        return '<a   href="index.php?app=omevirtualwms&ctl=admin_wms&act=getinfo&p[0]='.$row['bn'].'&p[1]='.$flag.'&p[2]='.$row['type_id'].'">开始模拟</a>';
    }

    function row_style($row)
    {
        if (in_array($row['bn'],app::get('omevirtualwms')->model('stockin')->queue)) {
            return "\" style=\"background-color:#ffe3e7;\"";
        }
    }

    var $column_msg = '备注';
    var $column_msg_width = 400;
    var $column_msg_order = 200;
    function column_msg($row)
    {
        if (in_array($row['bn'],app::get('omevirtualwms')->model('stockin')->queue)) {
            return app::get('omevirtualwms')->_(TIP_INFO);
        }
    }

}
