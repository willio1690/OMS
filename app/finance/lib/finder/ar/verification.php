<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_finder_ar_verification{
    var $column_edit = '操作';
    var $column_edit_order = 1;
    /**
     * column_edit
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_edit($row){
        $order_bn = $row['order_bn'];
        $time_from = strtotime($_POST['time_from']." 00:00:00");
        $time_to = strtotime($_POST['time_to']." 23:59:59");
        $href = sprintf('<a href="index.php?app=finance&ctl=ar_verification&act=verificate&finder_id=%s&order_bn=%s&time_from=%s&time_to=%s" target="dialog::{width:960,height:460}">应收对冲</a>',$_GET['_finder']['finder_id'],$order_bn,$time_from,$time_to);
        return $href;
    }
}