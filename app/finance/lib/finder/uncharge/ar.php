<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_finder_uncharge_ar{


	var $column_edit = "操作";
    var $column_edit_width = "100";
    var $column_edit_order=5;
    function column_edit($row){

        $confhref .= sprintf('<a href="index.php?app=finance&ctl=monthend_uncharge&act=reset&p[0]=%s&p[1]=%s&finder_id=%s" target="_blank">更改账期</a>&nbsp;&nbsp;&nbsp;&nbsp;',$row['ar_id'],$_GET['p'][0],$_GET['_finder']['finder_id']);

        return $confhref;
    }
}