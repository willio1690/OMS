<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omevirtualwms_finder_storeprocess{

    
    var $column_control = '操作';
	var $column_control_width = 100;
    function column_control($row){
    	$flag = "storeprocess";
        return '<a  href="index.php?app=omevirtualwms&ctl=admin_wms&act=getinfo&p[0]='.$row['mp_bn'].'&p[1]='.$flag.'">开始模拟</a>';
    }

}
