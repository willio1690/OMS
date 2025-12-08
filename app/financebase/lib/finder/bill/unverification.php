<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class financebase_finder_bill_unverification{

	var $column_edit = "操作";
    var $column_edit_width = "80";
    function column_edit($row) {
        $finder_id = $_GET['_finder']['finder_id'];
        $ret = '';
       
        $ret .= '<a href="index.php?app=financebase&ctl=admin_shop_settlement_bill&act=detailVerification&p[0]='.$row['order_bn'].'&_finder[finder_id]=' . $finder_id . '&finder_id=' . $finder_id . '" target="_blank">查看详情</a>';
        

        return $ret;
    }

}

