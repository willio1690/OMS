<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class financebase_finder_bill_fee_type {

	var $column_edit = "设置";
    var $column_edit_width = "80";

    function column_edit($row) {
        $finder_id = $_GET['_finder']['finder_id'];

        if(!$this->shop_list)
        {
        	$this->shop_list = array_column(financebase_func::getShopList(financebase_func::getShopType()),null,'shop_id');
        }
        $ret = '';
        if(isset($this->shop_list[$row['shop_id']]) && $this->shop_list[$row['shop_id']]['shop_type'] == 'taobao' && $this->shop_list[$row['shop_id']]['business_type'] == 'fx'  )
        {
        	$ret .= '<a href="index.php?app=financebase&ctl=admin_shop_settlement_fee_type&act=setWhiteList&p[0]='.$row['fee_type_id'].'&_finder[finder_id]=' . $finder_id . '&finder_id=' . $finder_id . '" target="_blank">设置白名单</a>';
        }


        return $ret;
    }


}

