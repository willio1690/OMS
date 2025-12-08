<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_finder_bill_confirm{

    var $column_operator = '操作';
    var $column_operator_width = 65;
    var $column_operator_order = 1;
    function column_operator($row){
        $confirm_id = $row['confirm_id'];
        $finder_id = $_GET['_finder']['finder_id'];        
        
        $but1 = "<a href=\"index.php?app=finance&ctl=bill_confirm&act=confirm&p[0]={$confirm_id}&finder_id={$finder_id}\" target=\"dialog::{title:'记账', width:450, height:300}\">记账</a>";

        $but2 = sprintf('<a href="javascript:if (confirm(\'确定要作废当前账单吗,作废后将不可恢复？\')){W.page(\'index.php?app=finance&ctl=bill_confirm&act=do_cancel&p[0]=%s&finder_id=%s\', $extend({method: \'get\'}, JSON.decode({})), this);}void(0);" target="">作废</a>',$confirm_id,$finder_id);
        
        return $but1.' '.$but2;
    }

}
