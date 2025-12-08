<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_finder_verification{
    var $column_edit = "操作";
    function column_edit($row){
        $render = app::get('finance')->render();
        $log_id = $row['log_id'];
        $render->pagedata['log_id'] = $log_id;
        $render->pagedata['finder_id'] = $_GET['_finder']['finder_id'];
        return $render->fetch('verification/cancel.html');
    }

    function detail_cols($log_id){
        $render = app::get('finance')->render();
        $veriitemObj = &app::get('finance')->model('verification_items');
        $data = $veriitemObj->getList('*',array('log_id'=>$log_id));
        $render->pagedata['data'] = $data;
        return $render->fetch('verification/detail.html');
    }
}
?>